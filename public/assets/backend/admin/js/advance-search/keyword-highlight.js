window.addEventListener("load", function () {
    if (!document.getElementById('kw-hl-inline-style')) {
        var style = document.createElement('style');
        style.id = 'kw-hl-inline-style';
        style.textContent = [
            'mark.kw-hl, .kw-hl-wrap mark, mark, .mark {',
            '    padding: 0 1px !important;',
            '    margin: 0 !important;',
            '    background-color: #ff9d4a !important;',
            '    color: #1a1a1a !important;',
            '    border-radius: 2px;',
            '    font: inherit !important;',
            '    line-height: inherit !important;',
            '    display: inline !important;',
            '    box-decoration-break: clone;',
            '    -webkit-box-decoration-break: clone;',
            '}'
        ].join('\n');
        document.head.appendChild(style);
    }

    function getQueryParam(param) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    }

    function escapeRegex(value) {
        return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function buildHighlightedFragment(text, regex) {
        const wrapper = document.createElement('span');
        wrapper.className = 'kw-hl-wrap';

        text.split(regex).forEach(function (segment, index) {
            if (segment === '') {
                return;
            }

            if (index % 2 === 1) {
                const mark = document.createElement('mark');
                mark.className = 'kw-hl';
                mark.textContent = segment;
                wrapper.appendChild(mark);
            } else {
                wrapper.appendChild(document.createTextNode(segment));
            }
        });

        return wrapper;
    }

    function highlightText(keyword) {
        const trimmedKeyword = keyword.trim();

        if (!trimmedKeyword) {
            return;
        }

        const splitRegex = new RegExp('(' + escapeRegex(trimmedKeyword) + ')', 'giu');
        const matchRegex = new RegExp(escapeRegex(trimmedKeyword), 'iu');

        const skipSelector = [
            'input',
            'textarea',
            'select',
            'option',
            '[contenteditable="true"]',
            '[data-no-highlight]',
            '#advanceSearchModal'
        ].join(',');

        const walker = document.createTreeWalker(
            document.body,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: function (node) {
                    const parent = node.parentNode;
                    if (
                        parent &&
                        parent.nodeName !== "SCRIPT" &&
                        parent.nodeName !== "STYLE" &&
                        parent.nodeName !== "TEMPLATE" &&
                        !parent.closest("mark") &&
                        !parent.closest(skipSelector) &&
                        node.nodeValue.trim().length > 0
                    ) {
                        return NodeFilter.FILTER_ACCEPT;
                    }
                    return NodeFilter.FILTER_REJECT;
                }
            }
        );

        const nodesToReplace = [];

        while (walker.nextNode()) {
            if (matchRegex.test(walker.currentNode.nodeValue)) {
                nodesToReplace.push(walker.currentNode);
            }
        }

        nodesToReplace.forEach(function (node) {
            node.parentNode.replaceChild(buildHighlightedFragment(node.nodeValue, splitRegex), node);
        });
    }

    const keyword = getQueryParam("keyword");
    if (keyword) {
        highlightText(keyword);
    }
});
