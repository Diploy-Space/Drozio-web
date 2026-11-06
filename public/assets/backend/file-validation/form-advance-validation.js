"use strict";

function FormMagicValidation(form) {
    if (!form) return null;

    const showsErrorsInline = form.classList.contains('form-advance-inline-validation');
    const validatesInputs = form.classList.contains('form-advance-inputs-validation');
    const validatesFiles = form.classList.contains('form-advance-file-validation');

    const validator = new window.JustValidate(form, {
        validateBeforeSubmitting: true,
        lockForm: false,
        focusInvalidField: false,
        errorFieldCssClass: '',
        errorLabelCssClass: 'just-validate-error-label',
        errorLabelStyle: { color: 'red', fontSize: '0.85rem' },
        successFieldCssClass: '',
    });

    if (typeof validator.formSubmitHandler === 'function') {
        form.removeEventListener('submit', validator.formSubmitHandler);
    }

    let observer = null;

    if (!showsErrorsInline) {
        observer = new MutationObserver(() => {
            form.querySelectorAll('.just-validate-error-label, .just-validate-success-label')
                .forEach(el => el.remove());
        });
        observer.observe(form, { childList: true, subtree: true });
    }

    const getFieldLabel = (input) => input.closest('.form-group')?.querySelector('label')?.textContent?.replace(/\s+/g, ' ').trim()
        || input.placeholder || 'This field';

    const isSelect2Field = (input) => input.classList.contains('select2-hidden-accessible');

    const createInlineErrorContainer = (input) => {
        const anchor = input.closest('.upload-file, .select-wrapper, .input-group')
            || (isSelect2Field(input) && input.nextElementSibling?.classList.contains('select2-container') ? input.nextElementSibling : input);
        const container = document.createElement('div');
        container.className = 'form-advance-field-error';
        anchor.insertAdjacentElement('afterend', container);
        inlineErrorContainers.set(input, container);

        return container;
    };

    const inlineFields = new Set();

    form.querySelectorAll('input, textarea, select').forEach(input => {
        if (!input.name || input.type === 'hidden' || input.hasAttribute('hidden')) return;

        const name = input.name;
        if (!name) return;
        const rules = [];
        if (validatesInputs) {
            if (input.required && input.type === 'file') rules.push({ rule: 'required', errorMessage: input.dataset.requiredMsg || `${getFieldLabel(input)} is required` });
            if (input.type === 'email') rules.push({ rule: 'email', errorMessage: input.dataset.emailMsg || 'Please enter a valid email address' });
            if (input.type === 'number' || input.inputMode === 'numeric') rules.push({ rule: 'number', errorMessage: input.dataset.numberMsg || `${getFieldLabel(input)} must be a number` });
            if (input.type === 'url') rules.push({ rule: 'customRegexp', value: /^https?:\/\/.+/, errorMessage: input.dataset.urlMsg || 'Please enter a valid URL' });
            if (input.minLength) rules.push({ rule: 'minLength', value: parseInt(input.minLength), errorMessage: input.dataset.minlengthMsg || `${getFieldLabel(input)} must be at least ${input.minLength} characters` });
            if (input.maxLength && parseInt(input.maxLength) > 0) rules.push({ rule: 'maxLength', value: parseInt(input.maxLength), errorMessage: input.dataset.maxlengthMsg || `${getFieldLabel(input)} must be ${input.maxLength} characters or less` });
            if (input.min) rules.push({ rule: 'minNumber', value: parseFloat(input.min), errorMessage: input.dataset.minMsg || `${getFieldLabel(input)} must be ${input.min} or more` });
            if (input.max) rules.push({ rule: 'maxNumber', value: parseFloat(input.max), errorMessage: input.dataset.maxMsg || `${getFieldLabel(input)} must be ${input.max} or less` });
            if (input.pattern) rules.push({ rule: 'customRegexp', value: new RegExp(input.pattern), errorMessage: input.dataset.patternMsg || `${getFieldLabel(input)} has an invalid format` });

            if (isSelect2Field(input)) {
                rules.push({
                    rule: 'function',
                    validator: (val, field) => {
                        if (!field) return false;
                        let value = $(field).val();

                        if (value === undefined || value === null) return false;

                        if (Array.isArray(value)) return value.length > 0;

                        if (typeof value === 'string') return value.trim() !== '';

                        return false;
                    },
                    errorMessage: input.dataset.requiredMsg || `${getFieldLabel(input)} is required`
                });
            }

            if (name.endsWith('[]')) {
                rules.push({
                    rule: 'function',
                    validator: (val, field) => {
                        const inputs = form.querySelectorAll(`input[name="${name}"]`);
                        return Array.from(inputs).some(i => i.value && i.value.trim() !== '');
                    },
                    errorMessage: input.dataset.requiredMsg || `${getFieldLabel(input)} is required`
                });
            }
        }

        if (showsErrorsInline && input.required) {
            const requiredMessage = input.dataset.requiredMsg || `${getFieldLabel(input)} is required`;

            if (input.type === 'file') {
                if (!validatesFiles) rules.push({ rule: 'minFilesCount', value: 1, errorMessage: requiredMessage });
            } else if (!(validatesInputs && isSelect2Field(input))) {
                rules.push({ rule: 'required', errorMessage: requiredMessage });
            }
        }

        if (validatesFiles) {
            if (input.type?.toString() === 'file') {
                if (input.required) {
                    rules.push({
                        rule: 'minFilesCount',
                        value: 1,
                        errorMessage: input.dataset.requiredMsg || `${getFieldLabel(input)} is required`,
                    });
                }

                if (input.dataset.minFiles) {
                    rules.push({
                        rule: 'minFilesCount',
                        value: parseInt(input.dataset.minFiles, 10),
                        errorMessage: `Select at least ${input.dataset.minFiles} file(s)`
                    });
                }
                if (input.dataset.maxFiles) {
                    rules.push({
                        rule: 'maxFilesCount',
                        value: parseInt(input.dataset.maxFiles, 10),
                        errorMessage: `Select at most ${input.dataset.maxFiles} file(s)`
                    });
                }
            }
        }

        if (!rules.length) {
            console.warn(`Skipping "${name}" — no validation rules detected.`);
            return;
        }

        if (showsErrorsInline) {
            validator.addField(input, rules, { errorsContainer: createInlineErrorContainer(input) });
            inlineFields.add(input);
        } else {
            validator.addField(`[name="${name}"]`, rules);
        }
    });

    if (showsErrorsInline) {
        if (window.jQuery) {
            window.jQuery(form).on('change', 'select', function () {
                if (validator.isSubmitted && inlineFields.has(this)) {
                    validator.revalidateField(this).catch(() => {});
                }
            });
        }

        form.addEventListener('reset', () => {
            validator.isSubmitted = false;
            validator.clearErrors();
        });
    }

    return {
        async check() {
            if (showsErrorsInline) {
                validator.isSubmitted = true;
            }

            try {
                this.lastResult = await validator.validate();
                return this.lastResult?.isValid === true;
            } catch (e) {
                console.warn(e);
                return false;
            }
        },

        errors() {
            const list = [];

            const fields = validator.fields;

            if (!fields) return list;

            Object.entries(fields).forEach(([id, fieldState]) => {
                if (!fieldState.isValid && !fieldState.elem?.disabled) {
                    const message = fieldState.errorMessage ||
                        (fieldState.errors?.[0]) ||
                        `Field is invalid`;

                    list.push({
                        element: fieldState.elem,
                        message: message,
                        fieldName: fieldState.elem?.name || id
                    });
                }
            });

            return list;
        },

        destroy() {
            observer?.disconnect();
            validator.destroy?.();
        },

        validator
    };
}

const formValidators = new WeakMap();
const formSubmitting = new WeakSet();
const inlineErrorContainers = new WeakMap();

function initFormValidation() {
    document.querySelectorAll('.form-advance-validation').forEach(form => {
        if (!formValidators.has(form)) {
            formValidators.set(form, FormMagicValidation(form));
        }
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initFormValidation);
else initFormValidation();

if (typeof MutationObserver !== 'undefined') {
    new MutationObserver(() => initFormValidation()).observe(document.body, { childList: true, subtree: true });
}

document.addEventListener('submit', async function (e) {
    const form = e.target.closest('.form-advance-validation.non-ajax-form-validate');
    if (!form) return;

    e.preventDefault();
    if (formSubmitting.has(form)) return false;

    const validator = formValidators.get(form);
    if (!validator) return true;

    formSubmitting.add(form);

    const valid = await validator.check();
    const errors = validator.errors();

    if (!valid && errors?.length > 0) {
        reportFirstError(errors[0]);
        formSubmitting.delete(form);
        return false;
    }

    lockSubmittedForm(form);
    HTMLFormElement.prototype.submit.call(form);
});

function lockSubmittedForm(form) {
    form.querySelectorAll('[type="submit"]').forEach(button => {
        button.disabled = true;
    });
}

window.addEventListener('pageshow', function (event) {
    if (!event.persisted) return;

    document.querySelectorAll('.form-advance-validation.non-ajax-form-validate').forEach(form => {
        formSubmitting.delete(form);
        form.querySelectorAll('[type="submit"]').forEach(button => {
            button.disabled = false;
        });
    });
});

function reportFirstError(error) {
    const field = error.element;

    if (!inlineErrorContainers.has(field)) {
        showErrorToast(error.message);
    }

    if (!field) return;

    const visibleField = field.offsetParent !== null ? field : field.closest('.form-group') || field;

    visibleField.scrollIntoView({ behavior: 'smooth', block: 'center' });

    if (field.offsetParent !== null && typeof field.focus === 'function') {
        field.focus({ preventScroll: true });
    }
}

function showFileError(input, msg) {
    const container = inlineErrorContainers.get(input);

    if (!container) {
        showErrorToast(msg);
        return;
    }

    const label = document.createElement('div');
    label.className = 'just-validate-error-label';
    label.style.color = 'red';
    label.style.fontSize = '0.85rem';
    label.textContent = msg;
    container.replaceChildren(label);
}

function showErrorToast(msg) {
    if (typeof toastMagic !== 'undefined'){
        toastMagic.error(msg, '', true);
    } else{
        console.warn(msg);
    }
}


async function validateFormHelper(formElement) {
    const form = formElement instanceof jQuery ? formElement : $(formElement);
    if (formSubmitting.has(form)) return false;
    const validator = formValidators.get(form[0]);
    if (!validator) return true;
    formSubmitting.add(form);

    const valid = await validator.check();

    const errors = validator.errors();

    if (!valid && errors?.length > 0) {
        reportFirstError(errors[0]);
        formSubmitting.delete(form);
        return false;
    }

    return true;
}


function formSubmitCleanup(formElement) {
    const form = formElement instanceof jQuery ? formElement : $(formElement);
    formSubmitting.delete(form);
}


document.querySelectorAll('.form-advance-validation input[type="file"]').forEach(input => {
    input.addEventListener('change', function () {

        if (this.type !== 'file') return true;

        const files = this.files;
        const required = this.required;
        const maxSizeMb = parseInt(this.dataset.maxSize || 3, 10);
        const accept = (this.getAttribute('accept') || '')
            .split(',')
            .map(t => t.trim().toLowerCase())
            .filter(Boolean);

        let isValid = true;

        if (required && (!files || files.length === 0)) {
            isValid = false;
            if (!inlineErrorContainers.has(this)) {
                showErrorToast(`${getFieldLabel(this)} is required`);
            }
            removeInputConnectedElements(this);
            return;
        }

        if (!files || files.length === 0) return;

        for (const f of files) {
            if (f.size > maxSizeMb * 1024 * 1024) {
                isValid = false;
                showFileError(this, `${f.name} exceeds ${maxSizeMb}MB`);
                removeInputConnectedElements(this);
                break;
            }

            if (
                accept.length &&
                !accept.some(a =>
                    a.startsWith('.')
                        ? f.name.toLowerCase().endsWith(a)
                        : f.type.toLowerCase() === a
                )
            ) {
                isValid = false;
                showFileError(this, `${f.name} has an invalid file type`);
                removeInputConnectedElements(this);
                break;
            }
        }

        if (!isValid) {
            this.value = '';
        }
    });
});

function getFieldLabel(input) {
    return (
        input.dataset.label ||
        input.name ||
        input.id ||
        'This field'
    ).replace(/[_\[\]]+/g, ' ').trim();
}

function removeInputConnectedElements(input) {
    const container = input.closest('.custom_upload_input');
    if (container) {
        const deleteSections = container.querySelectorAll('.delete_file_input_section');
        deleteSections.forEach(section => section.classList.add('d-none'));
    }

    const cardContainer = input.closest('.upload-file');
    if (cardContainer) {
        setTimeout(() => {
            const uploadImgs = cardContainer.querySelectorAll('.upload-file-img');
            const textboxes = cardContainer.querySelectorAll('.upload-file-textbox');
            const overlays = cardContainer.querySelectorAll('.overlay');
            const removeBtns = cardContainer.querySelectorAll('.remove_btn');

            uploadImgs.forEach(img => {
                img.style.display = 'none';
                img.src = '';
            });

            textboxes.forEach(box => {
                box.style.display = '';
            });

            overlays.forEach(overlay => {
                overlay.classList.remove('show');
            });

            removeBtns.forEach(btn => {
                btn.style.opacity = 0;
            });

            cardContainer.classList.remove('input-disabled');
        }, 20)
    }

    let uploadZone = input.closest('.upload-zone');
    if (uploadZone) {
        setTimeout(() => {
            const textboxes = uploadZone.querySelectorAll('.text-box');

            textboxes.forEach(box => {
                box.style.display = '';
                box.classList.remove('d-none');
            });

        }, 20)
    }
}


function getXhrErrorMessages(xhr) {
    const body = xhr?.responseJSON;

    if (!body) {
        return [];
    }

    if (body.error) {
        return [body.error];
    }

    if (Array.isArray(body.errors)) {
        return body.errors
            .map(entry => (entry && typeof entry === 'object' ? entry.message : entry))
            .filter(Boolean);
    }

    if (body.errors && typeof body.errors === 'object') {
        return Object.values(body.errors)
            .reduce((messages, entry) => messages.concat(entry), [])
            .filter(Boolean);
    }

    return body.message ? [body.message] : [];
}

function xhrResponseManager(xhr) {
    const messages = getXhrErrorMessages(xhr);

    toastMagic.error(messages[0] || 'An unexpected error occurred.');
}
function ajaxResponseManager(response) {
    if (response.status === "success") {
        if (response.message) {
            toastMagic.success(response.message);
        }

        setTimeout(() => {
            if (response?.redirectRoute) {
                location.href = response.redirectRoute;
            } else if (response?.redirect_url) {
                location.href = response?.redirect_url;
            }
        }, 500)
    } else if (response.status === "error") {
        if (response.message) {
            toastMagic.error(response.message);
        }
    } else if (response.status === "warning") {
        if (response.message) {
            toastMagic.warning(response.message);
        }
    }

    if (response.errors) {
        for (let index = 0; index < response.errors.length; index++) {
            toastMagic.error(response.errors[index].message, {
                CloseButton: true,
                ProgressBar: true,
            });
        }
    } else if (response.error) {
        toastMagic.error(response.error, {
            CloseButton: true,
            ProgressBar: true,
        });
    }

    if (response?.reload) {
        location.reload();
    }
}
