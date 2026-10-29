---
name: Rescate de Alimentos · Mobile v2
description: Fotografía de alimentos, crema melocotón y acciones cálidas para compartir una nueva mesa.
colors:
  accent: "#D64114"
  orange: "#F45116"
  red: "#8C1D12"
  ink: "#3B211B"
  muted: "#7B6256"
  paper: "#FFF2E4"
  white: "#FFFBF5"
  peach: "#FFE4CB"
  line: "#EEDDCF"
  white-text: "#FFFFFF"
  sidebar: "#FFF8EF"
  field: "#FFFCF8"
  field-border: "#E5D1C2"
  secondary: "#FFE2C5"
  map-action: "#FFF0DE"
  selected-category: "#FFD6B2"
  selected-category-border: "#F2A16D"
  reserved: "#FFD091"
  reserved-ink: "#743411"
  delivered: "#EDE2D3"
  delivered-ink: "#5E4838"
  expired: "#EFDDD2"
  expired-ink: "#755247"
  focus: "#9B2D13"
typography:
  display:
    fontFamily: "Manrope, 'Segoe UI', sans-serif"
    fontSize: "clamp(30px, 3.5vw, 48px)"
    fontWeight: 800
    lineHeight: 1.08
    letterSpacing: "-0.035em"
  display-mobile:
    fontFamily: "Manrope, 'Segoe UI', sans-serif"
    fontSize: "25px"
    fontWeight: 800
    lineHeight: 1.13
    letterSpacing: "-0.035em"
  headline:
    fontFamily: "Manrope, 'Segoe UI', sans-serif"
    fontSize: "27px"
    fontWeight: 800
    lineHeight: 1.3
    letterSpacing: "-0.035em"
  title:
    fontFamily: "Manrope, 'Segoe UI', sans-serif"
    fontSize: "15px"
    fontWeight: 800
    lineHeight: 1.4
    letterSpacing: "-0.025em"
  title-mobile:
    fontFamily: "Manrope, 'Segoe UI', sans-serif"
    fontSize: "13px"
    fontWeight: 800
    lineHeight: 1.37
    letterSpacing: "-0.025em"
  body:
    fontFamily: "Manrope, 'Segoe UI', sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.6
    letterSpacing: "-0.015em"
  label:
    fontFamily: "Manrope, 'Segoe UI', sans-serif"
    fontSize: "12px"
    fontWeight: 800
    lineHeight: 1.5
    letterSpacing: "-0.015em"
  pickup-fact:
    fontFamily: "Manrope, 'Segoe UI', sans-serif"
    fontSize: "12px"
    fontWeight: 400
    lineHeight: 1.6
    letterSpacing: "-0.015em"
rounded:
  badge: "7px"
  field: "13px"
  control: "14px"
  card: "16px"
  card-wide: "17px"
  feature: "21px"
  surface: "23px"
  feature-wide: "24px"
  dialog: "25px"
  bottom-nav: "28px"
  search: "29px"
  circle: "50%"
spacing:
  xs: "4px"
  sm: "8px"
  compact: "12px"
  md: "16px"
  lg: "24px"
  xl: "32px"
components:
  button-primary:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.white-text}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    padding: "12px 18px"
  button-secondary:
    backgroundColor: "{colors.secondary}"
    textColor: "{colors.red}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    padding: "12px 18px"
  button-light:
    backgroundColor: "{colors.white}"
    textColor: "{colors.red}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    padding: "12px 18px"
  search:
    backgroundColor: "{colors.white}"
    textColor: "{colors.ink}"
    rounded: "{rounded.search}"
    padding: "13px 20px 13px 53px"
    height: "52px"
  field:
    backgroundColor: "{colors.field}"
    textColor: "{colors.ink}"
    rounded: "{rounded.field}"
    padding: "11px 13px"
  food-card:
    backgroundColor: "{colors.white}"
    textColor: "{colors.ink}"
    rounded: "{rounded.card-wide}"
  status-available:
    backgroundColor: "{colors.red}"
    textColor: "{colors.white-text}"
    rounded: "{rounded.badge}"
    padding: "5px 8px"
  status-reserved:
    backgroundColor: "{colors.reserved}"
    textColor: "{colors.reserved-ink}"
    rounded: "{rounded.badge}"
    padding: "5px 8px"
  bottom-navigation:
    backgroundColor: "{colors.white}"
    textColor: "{colors.muted}"
    rounded: "{rounded.bottom-nav}"
    padding: "8px 6px 7px"
  dialog:
    backgroundColor: "{colors.white}"
    textColor: "{colors.ink}"
    rounded: "{rounded.dialog}"
    width: "min(100%, 680px)"
---

# Design System: Rescate de Alimentos

## Overview

**Creative North Star: «Una nueva mesa».** Alimentos grandes y reconocibles, superficies crema melocotón y acentos cálidos hacen que compartir comida se sienta cercano y concreto. La identidad procede de la referencia móvil elegida expresamente por el usuario el 27 de octubre de 2026 y sustituye la identidad verde anterior.

La fotografía lleva la expresión; el texto y los controles conservan una lectura compacta de aplicación móvil. El rojo profundo enmarca las imágenes y da peso a títulos y cantidades. El naranja de acción dirige la participación; el naranja vivo distingue el destino activo de la navegación. La composición concreta de esta webapp se registra en `.impeccable/surfaces/mobile-webapp.md`.

**Key Characteristics:**

- Fotografía de estudio cálida y alimentos completos, sin texto incrustado.
- Manrope compacta, títulos firmes y cifras de cantidad fáciles de comparar.
- Superficies suaves, categorías circulares e iconos lineales.
- Estados y acciones expresados con palabras concretas del rescate.

## Colors

La paleta combina un campo crema luminoso con rojo alimentario profundo y dos naranjas con funciones distintas. Los valores normativos están en el frontmatter.

### Primary

- **Naranja de acción** (`accent`): fondo de botones principales, flechas de tarjeta e indicadores de recogida. Es el valor predeterminado del acento configurable del cliente.
- **Naranja vivo** (`orange`): círculo activo de la navegación inferior y firma gráfica del emblema.
- **Rojo profundo** (`red`): marca, títulos, cantidades, enlaces y estado disponible; también guía el carácter del hero fotográfico.

### Neutral

- **Crema melocotón** (`paper`): fondo continuo de la aplicación, tema y acceso WordPress.
- **Blanco cálido** (`white`): tarjetas, búsqueda, navegación, formularios y diálogos.
- **Tinta cacao** (`ink`) y **marrón secundario** (`muted`): texto principal y contexto.
- **Melocotón** (`peach`, `secondary`, `map-action`): controles secundarios y zonas de ayuda; los separadores emplean `line`.
- **Campo claro** (`field`) y **borde de campo** (`field-border`): identifican entradas editables sin elevarlas como tarjetas.

Los estados reservado, entregado y vencido usan sus pares de fondo/tinta del frontmatter y siempre muestran una etiqueta. El foco utiliza un contorno independiente de la selección.

**The Action Contrast Rule.** El acento personalizado sustituye el valor predeterminado; el frontend elige blanco o negro para el texto del botón según contraste. No fijar texto blanco sobre cualquier acento del cliente.

## Typography

**Display Font / Body Font:** Manrope variable alojada localmente; la aplicación usa Segoe UI y sans-serif como respaldo. El tema y el acceso usan system-ui como respaldo. El archivo disponible cubre pesos de 200 a 800; la interfaz utiliza principalmente 400, 650, 750 y 800.

**Character:** una sola familia, abierta y redondeada, con títulos densos y texto de tarea sin adornos. La diferencia de jerarquía procede de tamaño, peso y espacio, sin introducir una segunda familia.

### Hierarchy

- **Display / display-mobile:** mensaje principal sobre fotografía; el ajuste móvil conserva palabras enteras y admite varias líneas.
- **Headline:** encabezados de página; en móvil pasan a un tamaño compacto (23px). Los títulos de secciones de ofertas tienen su propia adaptación (24px / 21px).
- **Title / title-mobile:** nombres de alimentos; hasta dos líneas y altura reservada para alinear las tarjetas.
- **Body:** texto base de la aplicación. La información contextual utiliza tamaños de 10–12px según componente.
- **Label:** acciones breves y firmes. Los filtros de categoría usan peso intermedio (650), que sube a 800 al seleccionar.
- **Pickup-fact:** lugar y horario de las tarjetas conservan tamaño legible (12px) en escritorio y móvil. Las confirmaciones con casilla también mantienen 12px.

Las cantidades usan cifras tabulares. Los campos móviles usan 16px para mantener una lectura cómoda durante la captura de datos; no heredan el tamaño reducido de sus etiquetas.

**The Whole Word Rule.** Los títulos del hero se parten por palabras; no reducir tipografía ni cortar sílabas para imitar una línea de la referencia.

## Layout

El sistema usa contenedores flexibles y grillas con columnas que pueden contraerse. El contenido principal llega a un ancho máximo de 1500px. La escala de separación del frontmatter se combina con medidas específicas de cada componente, sin una retícula rígida impuesta a todo el contenido.

La webapp tiene cuatro cambios de composición: a 1500px aparecen cuatro columnas; la grilla base tiene tres; a 900px pasa a dos y el rail se comprime; a 600px el rail desaparece y aparece la navegación inferior. La variante de hasta 375px compacta títulos y márgenes. El breakpoint de 1180px convierte el formulario con apoyo lateral en una sola columna.

En móvil, el contenido tiene márgenes laterales (16px), tarjetas en dos columnas y separaciones de fila/columna distintas (15px / 12px). Las categorías se desplazan horizontalmente: cada filtro conserva ancho mínimo (76px) y separación (14px). El borde de una categoría parcialmente visible comunica que la fila continúa.

La navegación inferior flota sobre el borde seguro del dispositivo; el shell reserva espacio inferior. Los formularios priorizan una columna de lectura y usan dos campos por fila solo donde el contenido lo permite. Los diálogos separan cabecera y cuerpo desplazable para conservar el cierre visible. La aplicación, el shell y los límites de altura del diálogo declaran `100vh` antes de `100svh` como fallback; los navegadores que admiten la unidad pequeña conservan el comportamiento visual existente.

## Elevation & Depth

La profundidad es cálida y ambiental: fondos tonales, fotografías y sombras de baja opacidad separan superficies sin convertir cada bloque en un panel. La navegación flotante y el diálogo sí necesitan una elevación mayor para indicar su relación con la página.

### Shadow Vocabulary

- **Búsqueda:** `0 5px 18px rgba(112,45,20,.045)`.
- **Tarjeta:** `0 7px 23px rgba(112,45,20,.055)`.
- **Panel de formulario:** `0 8px 28px rgba(112,45,20,.045)`.
- **Navegación inferior:** `0 10px 38px rgba(112,45,20,.16)`.
- **Destino activo:** `0 5px 13px rgba(177,58,14,.14)`.
- **Diálogo:** `0 22px 65px rgba(64,23,10,.22)`; fondo atenuado `rgba(56,22,12,.58)`.

**The Warm Depth Rule.** Usar la sombra del componente correspondiente; las sombras oscuras neutras, el brillo y el cristal no forman parte de este sistema.

## Shapes

Las categorías, flechas pequeñas y símbolos activos son circulares. Búsqueda y navegación usan contornos largos y redondeados; las tarjetas y superficies de tarea conservan esquinas suaves de menor radio. Los radios concretos del frontmatter distinguen estas funciones, sin aplicar una sola curva a todo.

La fotografía de categoría se recorta en círculo; la imagen de alimento de una tarjeta usa contención para mostrar el conjunto completo. Hero y franja promocional usan cobertura. Los bordes finos identifican campos, separadores y selección, sin competir con la fotografía.

## Components

### Buttons

Acciones compactas y directas. El botón principal usa `accent`, el secundario melocotón y el ligero blanco cálido; ambos secundarios llevan texto rojo. El botón general tiene altura mínima (45px), relleno del frontmatter y peso fuerte. El hover reduce ligeramente el brillo; los deshabilitados reducen opacidad y muestran espera.

El foco general es un contorno de 3px con separación de 4px. Algunos botones promocionales compactos son menores que el botón de tarea; no trasladar sus medidas a confirmaciones o formularios. El cierre modal y el acceso rápido al mapa del detalle tienen una zona de acción mínima (44px).

### Chips and status

Las etiquetas de estado son cortas y sólidas; disponible usa rojo y blanco, reservado melocotón dorado con tinta marrón, entregado y vencido tonos neutrales distintos. Los filtros de estado son píldoras claras que pasan a rojo al seleccionarse. La selección también se expresa mediante `aria-pressed`.

### Cards / Containers

Las tarjetas de alimento combinan fotografía, etiqueta de estado, título, comercio, ubicación, horario, cantidad y flecha. El cuerpo conserva alturas de título coherentes; la cantidad es la cifra dominante, sin introducir precios. La franja de mapa es una acción independiente de la apertura del detalle y conserva etiqueta explícita.

Los formularios, cuenta, acceso y estados vacíos reutilizan el blanco cálido y la misma familia de radios. Los estados vacíos, fallo, información anterior, desconexión y éxito tienen mensajes diferentes; la apariencia no debe confundir espera con confirmación.

### Inputs / Fields

La búsqueda es una píldora con icono lineal a la izquierda. Los campos de tarea tienen borde fino, fondo ligeramente más claro, etiqueta persistente y error asociado. Un campo inválido cambia borde y fondo y añade texto; no depende solo del color. Las casillas usan el acento y su texto de confirmación conserva la medida de tarea.

### Navigation

En móvil, la barra inferior tiene fondo blanco cálido, cuatro destinos como máximo según rol y etiquetas visibles. El símbolo activo crece en un círculo naranja (51px) que sobresale levemente; el símbolo inactivo es menor (39px). El destino activo también cambia peso y color de texto.

En escritorio, un rail cálido con marca y etiquetas ocupa 228px; entre 601px y 900px se reduce a 85px y conserva símbolos. Los destinos proceden de capacidades reales: el lenguaje visual no añade pantallas de compra ni funciones ficticias.

### Photography and feature banners

Los ocho WebP de `plugin/rescate-alimentos/assets/images/v2/` forman una sola familia: `hero`, `promo`, `vegetables`, `fruit`, `bakery`, `prepared`, `dairy` y `pantry`. Son imágenes ilustrativas generadas con IA; su procedencia y prompts están en `asset-sources.json` y sus sidecars. No son fotografías aportadas por comercios reales.

El hero reserva la izquierda para texto blanco y la derecha para alimentos sobre rojo profundo. En móvil incorpora una base tonal roja estable bajo el texto. La franja promocional aplica el mismo principio con crema melocotón; nunca colocar texto directamente sobre una zona de alimento sin protección tonal. Los textos y botones se renderizan en código.

### Dialogs and pickup planning

Los diálogos son superficies de tarea con cabecera persistente fuera del área desplazable. La X conserva su posición al desplazarse el mapa o el formulario. La lógica captura y devuelve el foco, permite cerrar con Escape y mantiene el cuerpo de fondo inmóvil.

La planificación de una donación real aparece como una zona melocotón dentro del detalle, con destino, pasos y acción de ruta explícitos. «Mapa y ruta» se ve desde la tarjeta y bajo el título del detalle. La apariencia del mapa no sustituye un punto confirmado: los registros reales sin pin muestran búsqueda y ruta externa; el error de carga conserva una alternativa visible.

Las demostraciones usan «Mapa de ejemplo» desde tarjeta y detalle. Su panel «Mapa de ejemplo · Cali» muestra una vista pública de la ciudad, sin marcador de comercio, ruta de recogida, copia de dirección ni solicitud de ubicación. El aviso visible aclara: «Vista ilustrativa de la ciudad. No ubica al comercio ni representa una recogida real». «Ampliar mapa de ejemplo» abre la misma vista pública de OpenStreetMap; si falla el módulo, quedan ese enlace y la acción de recarga. Esta variante reutiliza los colores, radios, tipografía y controles existentes.

La única animación continua es el indicador de carga (0.8s, lineal); las fotos de tarjeta responden al hover con escala mínima (1.035) en 0.3s. `prefers-reduced-motion` elimina animaciones, transiciones y desplazamiento suave.

## Do's and Don'ts

### Do:

- **Do** conservar la paleta predeterminada y respetar la marca, el logotipo, el acento y la copia configurados por el cliente.
- **Do** mantener alimentos reconocibles, categorías circulares y una sola familia fotográfica.
- **Do** dar prioridad visual a cantidad, lugar, hora límite y estado real del rescate.
- **Do** conservar etiquetas, foco visible y un cierre modal accesible durante todo el desplazamiento.
- **Do** mantener el crédito «Desarrollado por Moreno Technology» en las superficies que ya lo muestran.

### Don't:

- **Don't** recuperar la identidad verde anterior ni mezclarla con esta dirección.
- **Don't** añadir precios, carrito, ratings, estrellas de reseñas, notificaciones ficticias, métricas inventadas o emojis.
- **Don't** presentar fotografía ilustrativa o registros de demostración como evidencia de una oferta real.
- **Don't** colocar texto sobre alimentos sin una zona tonal de lectura estable.
- **Don't** reducir ubicación, horario o confirmaciones con casilla por debajo del tamaño de tarea documentado.
