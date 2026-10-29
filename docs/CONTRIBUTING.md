# Colaboración para la entrega

`feature/wordpress-plugin` contiene el plugin 2.0.1, su administración, interfaz, mapas, assets, pruebas y guía. `develop` conserva ese corte para revisión.

El aporte reservado es `feature/mobile-theme`: Diego incorpora `theme/rescate-mobile/` y `entrega/rescate-mobile-theme.zip` usando su propia identidad. El tema es versión 2.0.0 y depende del plugin del corte. No se crean commits en su nombre.

Los PR deben describir el cambio, los criterios comprobados y las pruebas realmente ejecutadas. La integración en `main` conserva la revisión humana y condiciones de la clase. Los documentos iniciales de `docs/spec/` permanecen como antecedentes; el MVP entregado funciona sobre WordPress, sin integración automatizada de WhatsApp ni Bubble.

Commits: `tipo(alcance): descripción`. Nunca añadir accesos, cookies, nonces, bases de datos del hosting ni archivos .env.
