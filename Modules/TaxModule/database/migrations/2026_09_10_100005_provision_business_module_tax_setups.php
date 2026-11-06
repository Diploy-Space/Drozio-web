<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->taxTablesAreScoped()) {
            return;
        }

        $retailSetup = DB::table('system_tax_setups')
            ->where('business_module', BusinessModule::RETAIL)
            ->where('is_default', 1)
            ->first();

        if ($retailSetup === null) {
            return;
        }

        foreach ([BusinessModule::WHOLESALE, BusinessModule::AUCTION] as $businessModule) {
            DB::transaction(function () use ($retailSetup, $businessModule): void {
                $setupId = $this->provisionSetup($retailSetup, $businessModule);
                $taxMap = $this->cloneRetailTaxes($businessModule);

                $this->remapSetupTaxIds($setupId, $taxMap);
            });
        }
    }

    public function down(): void {}

    private function taxTablesAreScoped(): bool
    {
        return Schema::hasTable('system_tax_setups')
            && Schema::hasTable('taxes')
            && Schema::hasColumn('system_tax_setups', 'business_module')
            && Schema::hasColumn('taxes', 'business_module');
    }

    private function provisionSetup(object $retailSetup, string $businessModule): int
    {
        $existing = DB::table('system_tax_setups')
            ->where('business_module', $businessModule)
            ->where('is_default', 1)
            ->first();

        if ($existing === null) {
            $columns = (array) $retailSetup;
            unset($columns['id']);
            $columns['business_module'] = $businessModule;
            $columns['created_at'] = now();
            $columns['updated_at'] = now();

            $setupId = (int) DB::table('system_tax_setups')->insertGetId($columns);
            $this->cloneAdditionalSetups((int) $retailSetup->id, $setupId);

            return $setupId;
        }

        if ($this->isUnconfigured($existing)) {
            DB::table('system_tax_setups')->where('id', $existing->id)->update([
                'tax_type' => $retailSetup->tax_type,
                'country_code' => $retailSetup->country_code,
                'tax_payer' => $retailSetup->tax_payer,
                'tax_ids' => $retailSetup->tax_ids,
                'is_active' => $retailSetup->is_active,
                'is_included' => $retailSetup->is_included,
                'updated_at' => now(),
            ]);

            $this->cloneAdditionalSetups((int) $retailSetup->id, (int) $existing->id);
        }

        return (int) $existing->id;
    }

    private function isUnconfigured(object $setup): bool
    {
        $taxIds = json_decode((string) $setup->tax_ids, true);

        return (int) $setup->is_active === 0 && (! is_array($taxIds) || $taxIds === []);
    }

    private function cloneAdditionalSetups(int $retailSetupId, int $targetSetupId): void
    {
        if (! Schema::hasTable('tax_additional_setups')) {
            return;
        }

        foreach (DB::table('tax_additional_setups')->where('system_tax_setup_id', $retailSetupId)->get() as $source) {
            $alreadyCloned = DB::table('tax_additional_setups')
                ->where('system_tax_setup_id', $targetSetupId)
                ->where('name', $source->name)
                ->exists();

            if ($alreadyCloned) {
                continue;
            }

            $columns = (array) $source;
            unset($columns['id']);
            $columns['system_tax_setup_id'] = $targetSetupId;
            $columns['created_at'] = now();
            $columns['updated_at'] = now();

            DB::table('tax_additional_setups')->insert($columns);
        }
    }

    /** @return array<int, int> */
    private function cloneRetailTaxes(string $businessModule): array
    {
        $map = [];

        foreach (DB::table('taxes')->where('business_module', BusinessModule::RETAIL)->orderBy('id')->get() as $tax) {
            $existing = DB::table('taxes')
                ->where('business_module', $businessModule)
                ->where('name', $tax->name)
                ->where('tax_rate', $tax->tax_rate)
                ->where(function ($query) use ($tax): void {
                    $tax->country_code === null
                        ? $query->whereNull('country_code')
                        : $query->where('country_code', $tax->country_code);
                })
                ->first();

            if ($existing !== null) {
                $map[(int) $tax->id] = (int) $existing->id;

                continue;
            }

            $columns = (array) $tax;
            unset($columns['id']);
            $columns['business_module'] = $businessModule;
            $columns['created_at'] = now();
            $columns['updated_at'] = now();

            $map[(int) $tax->id] = (int) DB::table('taxes')->insertGetId($columns);
        }

        return $map;
    }

    /** @param array<int, int> $taxMap */
    private function remapSetupTaxIds(int $setupId, array $taxMap): void
    {
        if ($taxMap === []) {
            return;
        }

        $setup = DB::table('system_tax_setups')->where('id', $setupId)->first();

        if ($setup !== null) {
            DB::table('system_tax_setups')
                ->where('id', $setupId)
                ->update(['tax_ids' => $this->remapTaxIds($setup->tax_ids, $taxMap)]);
        }

        if (! Schema::hasTable('tax_additional_setups')) {
            return;
        }

        foreach (DB::table('tax_additional_setups')->where('system_tax_setup_id', $setupId)->get() as $additionalSetup) {
            DB::table('tax_additional_setups')
                ->where('id', $additionalSetup->id)
                ->update(['tax_ids' => $this->remapTaxIds($additionalSetup->tax_ids, $taxMap)]);
        }
    }

    /** @param array<int, int> $taxMap */
    private function remapTaxIds(?string $taxIds, array $taxMap): ?string
    {
        $decoded = json_decode((string) $taxIds, true);

        if (! is_array($decoded)) {
            return $taxIds;
        }

        $remapped = array_map(
            static fn ($taxId): string => (string) ($taxMap[(int) $taxId] ?? (int) $taxId),
            $decoded,
        );

        return json_encode(array_values(array_unique($remapped)));
    }
};
