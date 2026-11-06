@php($helpTopicStatus = getWebConfig('vendor_registration_faq_status'))
@if($helpTopicStatus)
    <section class="pt-4 pt-lg-5">
        <div class="container">
            <div class="d-flex flex-column gap-2 align-items-center text-center">
                <h2 class="section-title mb-2">{{translate('Frequently asked questions')}}</h2>
                <p class="max-w-500 mb-4">{{translate('Got questions about becoming a vendor').' ? '.translate('Answers to common questions about joining as a vendor.')}}</p>
            </div>

            <div class="accordion accordion__custom accordion-flush" id="accordionFlushExample">
                @foreach($helpTopics as $key=>$topic)
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="flush-heading-{{$key}}">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse-{{$key}}" aria-expanded="false" aria-controls="flush-collapse-{{$key}}">
                                {{$topic->question}}
                            </button>
                        </h2>
                        <div id="flush-collapse-{{$key}}" class="accordion-collapse collapse" aria-labelledby="flush-heading-{{$key}}" data-bs-parent="#accordionFlushExample">
                            <div class="accordion-body">{{$topic->answer}}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
