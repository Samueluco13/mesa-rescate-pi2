# Plan de recogida (plugin 2.0.1)

El detalle de una donación organiza tres acciones: revisar el punto, abrir la ruta y coordinar la entrega. En los registros sin la marca `is_demo`, el mapa usa un pin opcional indicado por quien publica; una dirección sin pin conserva búsqueda y ruta externas, sin atribuirle coordenadas ni una coincidencia confirmada.

Los registros marcados `is_demo` muestran **Mapa de ejemplo · Cali** y la acción **Ver mapa de ejemplo**. La vista pública se centra en `3.4516, -76.532`, zoom 13, con el aviso «Vista ilustrativa de la ciudad. No ubica al comercio ni representa una recogida real». No dibuja un marcador de recogida ni usa coordenadas atribuidas al comercio, incluso si un registro de demostración contiene coordenadas. Tampoco ofrece rutas de recogida reales, copia de una dirección ficticia ni GPS. **Ampliar mapa de ejemplo** enlaza únicamente a la misma vista pública de OpenStreetMap.

El formulario de publicación ofrece un selector visual con zoom y arrastre. Solo «Confirmar este punto» guarda coordenadas en el borrador; su publicación conserva la validación, el rol y la clave de reintento del registro. Para confirmar, deben verse teselas en el punto central y el zoom debe permitir revisar las calles. La ubicación del dispositivo requiere pulsar «Centrar en mi ubicación» y conceder el permiso del navegador. Se consulta una vez para centrar el selector; no hay seguimiento y esta acción no existe en el mapa de demostración.

Leaflet 1.9.4 se incluye localmente con su licencia BSD-2-Clause. Las teselas se solicitan a OpenStreetMap únicamente cuando se abre el mapa, con atribución visible, HTTPS, Referer del navegador y caché normal. No hay geocodificación, precarga masiva ni mapas descargables. El servicio es de disponibilidad voluntaria y puede requerir otro proveedor si crece el tráfico.

El cargador espera tanto la hoja de estilos como el script local de Leaflet antes de inicializar el mapa. Un fallo permite reintentar la carga. Los fallos de teselas y el estado sin conexión muestran mensajes específicos; el reintento destruye la instancia anterior antes de crear otra. El tamaño se recalcula después de montar el mapa mediante `requestAnimationFrame`, cuando está disponible. Los diálogos incluyen un fallback `100vh` antes de `100svh` y desplazamiento automático al contenido; son medidas de compatibilidad, no una prueba de todos los navegadores móviles.

Para registros sin la marca de demostración, Google Maps URLs recibe el destino y, si se elige, el modo de transporte. El origen se decide en Maps. No requiere clave API; la aplicación no calcula tiempos, distancias ni rutas propias. Sin conexión se conserva la dirección de estos registros y se ofrece reintentar el mapa. En demostración se ofrece reintentar la vista ilustrativa, sin sugerir una dirección de recogida real.

Fuentes oficiales consultadas el 27 de octubre de 2026:

- [Leaflet Quick Start](https://leafletjs.com/examples/quick-start/) y [licencia 1.9.4](https://github.com/Leaflet/Leaflet/blob/v1.9.4/LICENSE).
- [OpenStreetMap Tile Usage Policy](https://operations.osmfoundation.org/policies/tiles/).
- [Google Maps URLs](https://developers.google.com/maps/documentation/urls/get-started).

Los campos de ubicación son opcionales y la migración agrega columnas sin sustituir donaciones, estados, reservas ni historial existentes. Una solicitud antigua que omitió coordenadas conserva el mismo hash al reintentarse.

El hotfix cambia la presentación y la carga del mapa; no agrega pines a las donaciones iniciales ni cambia su estado o titular. Ver `docs/mapa-compatibilidad.md` para separar el diagnóstico de la foto, las pruebas automatizadas y la evidencia de cada navegador/versión.
