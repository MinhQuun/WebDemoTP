(() => {
    const renderInlineQrs = () => {
        if (typeof QRCode === 'undefined') {
            return;
        }

        document.querySelectorAll('.qr-img').forEach((node) => {
            const text = node.dataset.qr || '';
            if (!text || node.dataset.rendered) return;

            new QRCode(node, {
                text,
                width: 60,
                height: 60,
                margin: 0,
            });

            node.dataset.rendered = '1';
        });
    };

    document.addEventListener('DOMContentLoaded', renderInlineQrs);
})();
