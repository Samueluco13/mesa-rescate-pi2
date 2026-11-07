(function () {
  'use strict';
  function setup() {
    document.querySelectorAll('#loginform, #lostpasswordform, #ra-settings-form').forEach(function (form) {
      form.noValidate = true;
      form.setAttribute('lang', 'es-CO');
      form.setAttribute('data-ra-validation', '');
      function clear(input) {
        input.removeAttribute('aria-invalid');
        var id = 'ra-wordpress-error-' + input.id;
        var error = document.getElementById(id);
        if (error) error.remove();
        var described = (input.getAttribute('aria-describedby') || '').split(/\s+/).filter(function (item) { return item && item !== id; });
        if (described.length) input.setAttribute('aria-describedby', described.join(' '));
        else input.removeAttribute('aria-describedby');
      }
      function message(input) {
        var value = input.value.trim();
        if (input.name === 'log' || input.name === 'user_login') return value ? '' : 'Escribe tu nombre de usuario o correo electrónico.';
        if (input.name === 'pwd') return input.value ? '' : 'Escribe tu contraseña.';
        if (input.name === 'ra_settings[brand_name]' && !value) return 'Escribe el nombre de la plataforma.';
        if (input.type === 'email' && value && input.validity.typeMismatch) return 'El correo de contacto no tiene un formato válido. Usa, por ejemplo, contacto@fundacion.org.';
        if (input.maxLength > 0 && Array.from(input.value).length > input.maxLength) return 'El máximo es ' + input.maxLength + ' caracteres. Acorta el texto.';
        return '';
      }
      form.addEventListener('submit', function (event) {
        var errors = [];
        form.querySelectorAll('input:not([type=hidden]):not([type=submit]):not([type=checkbox])').forEach(function (input) {
          clear(input);
          var text = message(input);
          if (!text) return;
          var error = document.createElement('p');
          error.id = 'ra-wordpress-error-' + input.id;
          error.className = 'ra-wordpress-field-error';
          error.textContent = text;
          var parent = input.closest('.wp-pwd') || input;
          parent.insertAdjacentElement('afterend', error);
          input.setAttribute('aria-invalid', 'true');
          input.setAttribute('aria-describedby', ((input.getAttribute('aria-describedby') || '') + ' ' + error.id).trim());
          errors.push({input: input, text: text});
        });
        var summary = form.querySelector('.ra-wordpress-error-summary');
        if (summary) summary.remove();
        if (!errors.length) return;
        event.preventDefault();
        summary = document.createElement('div');
        summary.className = 'ra-wordpress-error-summary';
        summary.setAttribute('role', 'alert');
        var title = document.createElement('p');
        title.textContent = errors.length === 1 ? 'Revisa el campo indicado antes de continuar.' : 'Revisa los ' + errors.length + ' campos indicados antes de continuar.';
        summary.appendChild(title);
        var list = document.createElement('ul');
        errors.forEach(function (item) { var li = document.createElement('li'); li.textContent = item.text; list.appendChild(li); });
        summary.appendChild(list);
        form.insertBefore(summary, form.firstChild);
        errors[0].input.focus();
      });
      form.addEventListener('input', function (event) {
        if (event.target.hasAttribute('aria-invalid') && !message(event.target)) {
          clear(event.target);
          var summary = form.querySelector('.ra-wordpress-error-summary');
          if (summary) summary.remove();
        }
      });
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setup);
  else setup();
})();
