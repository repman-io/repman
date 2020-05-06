(function() {
    window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)};ga.l=+new Date;
    ga('create', '{{ ga_tracking }}', 'auto');

    if (isOAuthReferrer(document.referrer)) {
        ga('set', 'referrer', '{{ url("index") }}');
    }

    ga('send', 'pageview');

    function isOAuthReferrer(referrer) {
        return referrer.indexOf('https://bitbucket.org/site/oauth2') === 0
            || [
                    'https://github.com/',
                    'https://gitlab.com/',
                    'https://app.buddy.works/'
                ].includes(referrer);
    }
})();
