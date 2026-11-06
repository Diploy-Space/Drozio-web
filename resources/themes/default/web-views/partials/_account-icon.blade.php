@if(auth('customer')->check())
    <img src="{{ getStorageImages(path: auth('customer')->user()->image_full_url, type: 'avatar') }}"
         alt="{{ auth('customer')->user()->f_name }}">
@else
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" fill="currentColor"/>
        <path d="M12 14.5c-4.14 0-7.5 2.35-7.5 5.25 0 .69.56 1.25 1.25 1.25h12.5c.69 0 1.25-.56 1.25-1.25 0-2.9-3.36-5.25-7.5-5.25Z" fill="currentColor"/>
    </svg>
@endif
