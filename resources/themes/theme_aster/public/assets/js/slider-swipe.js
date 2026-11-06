(function () {
    'use strict';

    const WHEEL_LINE_HEIGHT_PX = 16;
    const STEP_THRESHOLD_PX = 30;
    const STEP_SPEED_MS = 450;
    const GESTURE_GAP_MS = 200;
    const VERTICAL_DIRECTION = 'vertical';
    const swipeStates = new WeakMap();

    function horizontalWheelDelta(event) {
        const unit = event.deltaMode === 1 ? WHEEL_LINE_HEIGHT_PX : 1;
        const deltaX = event.deltaX * unit;
        const deltaY = event.deltaY * unit;

        if (event.shiftKey && deltaX === 0) {
            return deltaY;
        }

        return Math.abs(deltaX) > Math.abs(deltaY) ? deltaX : 0;
    }

    function owlInstanceOf(element) {
        if (!window.jQuery || !element.classList.contains('owl-carousel') || !element.classList.contains('owl-loaded')) {
            return null;
        }

        return window.jQuery(element).data('owl.carousel') || null;
    }

    function swiperInstanceOf(element) {
        const swiper = element.swiper;

        return swiper && typeof swiper.slideNext === 'function' && !swiper.destroyed ? swiper : null;
    }

    function owlSlider(element, owl) {
        return {
            isRtl: Boolean(owl.settings.rtl),
            canStep: function (movesForward) {
                if (owl.settings.loop) {
                    return owl.items().length > 1;
                }

                return movesForward ? owl.current() < owl.maximum() : owl.current() > owl.minimum();
            },
            step: function (movesForward) {
                window.jQuery(element).trigger(movesForward ? 'next.owl.carousel' : 'prev.owl.carousel', [STEP_SPEED_MS]);
            },
            onStepFinished: function (release) {
                window.jQuery(element).on('translated.owl.carousel', release);
            },
        };
    }

    function isSwiperSwipeable(swiper) {
        const hasOwnWheelControl = Boolean(swiper.params.mousewheel && swiper.params.mousewheel.enabled);

        return swiper.enabled !== false
            && !swiper.isLocked
            && swiper.params.allowTouchMove !== false
            && swiper.params.direction !== VERTICAL_DIRECTION
            && !hasOwnWheelControl;
    }

    function swiperSlider(swiper) {
        return {
            isRtl: Boolean(swiper.rtlTranslate),
            canStep: function (movesForward) {
                if (!isSwiperSwipeable(swiper)) {
                    return false;
                }

                if (swiper.params.loop) {
                    return swiper.slides.length > 1;
                }

                return movesForward ? swiper.allowSlideNext && !swiper.isEnd : swiper.allowSlidePrev && !swiper.isBeginning;
            },
            step: function (movesForward) {
                if (movesForward) {
                    swiper.slideNext(STEP_SPEED_MS);
                } else {
                    swiper.slidePrev(STEP_SPEED_MS);
                }
            },
            onStepFinished: function (release) {
                swiper.on('transitionEnd', release);
            },
        };
    }

    function sliderOf(element) {
        const owl = owlInstanceOf(element);

        if (owl) {
            return owlSlider(element, owl);
        }

        const swiper = swiperInstanceOf(element);

        return swiper ? swiperSlider(swiper) : null;
    }

    function swipeStateOf(element, slider) {
        let state = swipeStates.get(element);

        if (state) {
            return state;
        }

        state = { accumulatedDelta: 0, isStepLocked: false, gestureEndTimer: null };
        state.release = function () {
            state.isStepLocked = false;
            state.accumulatedDelta = 0;
        };

        slider.onStepFinished(state.release);
        swipeStates.set(element, state);

        return state;
    }

    function stepSlider(element, slider, delta, event) {
        const forwardDelta = slider.isRtl ? -delta : delta;
        const movesForward = forwardDelta > 0;

        if (!slider.canStep(movesForward)) {
            return false;
        }

        const state = swipeStateOf(element, slider);

        event.preventDefault();

        clearTimeout(state.gestureEndTimer);
        state.gestureEndTimer = setTimeout(state.release, GESTURE_GAP_MS);

        if (state.isStepLocked) {
            return true;
        }

        state.accumulatedDelta += forwardDelta;

        if (Math.abs(state.accumulatedDelta) >= STEP_THRESHOLD_PX) {
            state.isStepLocked = true;
            state.accumulatedDelta = 0;
            slider.step(movesForward);
        }

        return true;
    }

    function swipeSliderUnderPointer(event) {
        const delta = horizontalWheelDelta(event);

        if (delta === 0) {
            return;
        }

        for (let element = event.target; element && element !== document.body; element = element.parentElement) {
            const slider = sliderOf(element);

            if (slider && stepSlider(element, slider, delta, event)) {
                return;
            }
        }
    }

    document.addEventListener('wheel', swipeSliderUnderPointer, { passive: false });
})();
