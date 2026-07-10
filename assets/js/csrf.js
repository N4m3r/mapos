function getCookie(name) {
    var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    if (match) {
        return match[2];
    }
}

function setCsrfTokenInAllForms(csrfTokenName, csrfCookieName) {
    $('input[name="' + csrfTokenName + '"]').remove();
    var forms = document.querySelectorAll("form");
    forms.forEach(function (form) {
        var csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = csrfTokenName;
        csrfInput.value = getCookie(csrfCookieName);
        form.appendChild(csrfInput);
    });
}

$(document).ready(function () {
    // Add CSRF token input to each form and ajax requests
    var csrfTokenName = $('meta[name="csrf-token-name"]').attr('content');
    var csrfCookieName = $('meta[name="csrf-cookie-name"]').attr('content');

    setCsrfTokenInAllForms(csrfTokenName, csrfCookieName);

    $.ajaxSetup({
        credentials: "include",
        beforeSend: function (jqXHR, settings) {
            var csrfToken = getCookie(csrfCookieName);
            if (settings.data && typeof settings.data === 'object') {
                settings.data[csrfTokenName] = csrfToken;
            } else if (settings.data) {
                settings.data += '&' + $.param({
                    [csrfTokenName]: csrfToken
                });
            } else {
                // Requisições POST sem "data" (ex.: botões WhatsApp) também
                // precisam do token; sem este ramo o corpo virava
                // "undefined&token=..." e o CodeIgniter rejeitava com 403.
                settings.data = $.param({
                    [csrfTokenName]: csrfToken
                });
            }

            return true;
        },
        complete: function () {
            setCsrfTokenInAllForms(csrfTokenName, csrfCookieName);
        }
    });
});
