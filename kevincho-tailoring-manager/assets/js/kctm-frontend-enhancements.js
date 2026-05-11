/**
 * Kevin Cho — Frontend Enhancements
 * Newsletter subscribe, copy-link share, newsletter bar toggle.
 */
(function(){
    'use strict';

    /* ── Newsletter bar ───────────────────────────────────── */
    var bar = document.getElementById('kctm-newsletter-bar');
    if (bar) {
        /* Show after 4 seconds if not dismissed */
        var dismissed = sessionStorage.getItem('kctm_nl_dismissed');
        if (!dismissed) {
            setTimeout(function(){ bar.classList.add('kctm-nl-visible'); }, 4000);
        }

        /* Close button */
        var closeBtn = bar.querySelector('.kctm-newsletter-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function(){
                bar.classList.remove('kctm-nl-visible');
                sessionStorage.setItem('kctm_nl_dismissed', '1');
            });
        }

        /* Submit */
        var form = bar.querySelector('.kctm-newsletter-form');
        var msg  = bar.querySelector('.kctm-newsletter-msg');
        if (form) {
            form.addEventListener('submit', function(e){
                e.preventDefault();
                var emailInput = form.querySelector('.kctm-newsletter-email');
                var email = emailInput ? emailInput.value.trim() : '';
                if (!email) return;

                var submitBtn = form.querySelector('.kctm-newsletter-submit');
                if (submitBtn) submitBtn.textContent = '...';

                var fd = new FormData();
                fd.append('action', 'kctm_newsletter_subscribe');
                fd.append('nonce', (window.kctm_enhancements || {}).nonce || '');
                fd.append('email', email);

                fetch((window.kctm_enhancements || {}).ajax_url || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: fd
                })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if (msg) {
                        msg.textContent = data.data ? data.data.message : 'Subscribed!';
                        msg.style.display = 'block';
                    }
                    if (submitBtn) submitBtn.textContent = 'Subscribe';
                    if (emailInput) emailInput.value = '';
                    /* Auto-close after success */
                    setTimeout(function(){
                        bar.classList.remove('kctm-nl-visible');
                        sessionStorage.setItem('kctm_nl_dismissed', '1');
                    }, 3000);
                })
                .catch(function(){
                    if (msg) {
                        msg.textContent = 'Something went wrong. Please try again.';
                        msg.style.display = 'block';
                    }
                    if (submitBtn) submitBtn.textContent = 'Subscribe';
                });
            });
        }
    }

    /* ── Copy link share button ───────────────────────────── */
    document.querySelectorAll('.kctm-share-copy').forEach(function(btn){
        btn.addEventListener('click', function(){
            var url = btn.getAttribute('data-url');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function(){
                    btn.title = 'Copied!';
                    setTimeout(function(){ btn.title = 'Copy link'; }, 2000);
                });
            } else {
                /* Fallback */
                var ta = document.createElement('textarea');
                ta.value = url;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                btn.title = 'Copied!';
                setTimeout(function(){ btn.title = 'Copy link'; }, 2000);
            }
        });
    });
})();
