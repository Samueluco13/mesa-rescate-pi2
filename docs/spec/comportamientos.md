# Especificación del núcleo del producto

Estado: en revisión por Product Owner y QA. Los identificadores CMP y CA son la base de trazabilidad del backlog; deben mantenerse consistentes al aprobar cambios.

## Objetivo y alcance

Coordinar el rescate de alimentos entre comercios donantes, voluntarios y organizaciones receptoras, conservando una asignación única y trazabilidad de cada entrega.

El núcleo incluye registro, disponibilidad, reserva exclusiva, entrega y consulta de estado. Reportes de impacto, exportaciones, indicadores, asignación automática de destinos y notificaciones automáticas quedan fuera de esta versión del núcleo y requieren priorización explícita.

## Comportamientos y criterios

### SCRUM-7

Como comercio donante, quiero registrar una donación con los datos mínimos para ofrecer los alimentos disponibles y facilitar su recogida.
Comportamiento: CMP-01. Trazabilidad: HU-01 / RF-01; HU-13 / RF-02 para registro asistido. RNF-02, RNF-03, RNF-04, RNF-06 y RNF-09.
CA-01.1: tipo de alimento, cantidad positiva, lugar de recogida y hora límite futura permiten crear una donación Disponible con identificador único.
CA-01.2: campos incompletos o inválidos muestran un error específico, impiden guardar y conservan los datos válidos.
CA-01.3: un coordinador autorizado registra a nombre del comercio y deja constancia de autor, canal y origen asistido.
Prioridad P1: inicio del flujo. Dependencias: modelo de datos y permisos definidos. DoR: criterios aprobados por PO y QA. Las notificaciones automáticas se gestionan fuera del núcleo.

[Historia en Jira](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-7)

### SCRUM-8

Como voluntario, quiero consultar las donaciones disponibles para escoger una recogida compatible con mi disponibilidad.
Comportamiento: CMP-02. Trazabilidad: HU-03 / RF-03 y RF-05; RNF-01, RNF-02, RNF-04 y RNF-05.
CA-02.1: un voluntario autorizado ve donaciones disponibles y vigentes con tipo, cantidad, lugar de recogida y hora límite; las asignadas dejan de ofrecerse.
CA-02.2: la ausencia de resultados se distingue de un error de conexión; se ofrece reintentar sin presentar datos antiguos como confirmación actual.
Prioridad P1. Dependencias: CMP-01. DoR: criterios y reglas de visibilidad aprobados por PO y QA. Notificaciones automáticas y filtros avanzados quedan fuera del núcleo.

[Historia en Jira](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-8)

### SCRUM-9

Como voluntario, quiero reservar una donación de forma exclusiva para coordinar su recogida sin duplicar la asignación.
Comportamiento: CMP-03. Trazabilidad: HU-04 / RF-04; RNF-01 y RNF-09.
CA-03.1: ante dos solicitudes simultáneas, una sola reserva queda confirmada y asociada al voluntario ganador; el otro recibe un aviso claro de que la donación ya no está disponible.
CA-03.2: un reintento tras una interrupción de conexión no duplica la reserva ni modifica su titular.
Prioridad P1 por riesgo de duplicidad. Dependencias: CMP-01, CMP-02 y mecanismo de exclusión definido en el plan técnico. DoR: PO y QA validan criterios y escenario reproducible de concurrencia.

[Historia en Jira](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-9)

### SCRUM-10

Como coordinador, quiero registrar la entrega de una donación para conservar la trazabilidad del rescate.
Comportamiento: CMP-04. Trazabilidad: HU-08 / RF-09; RNF-06, RNF-07 y RNF-09.
CA-04.1: una donación reservada puede cerrarse por el responsable autorizado con organización receptora, fecha y hora y responsable; se conservan origen, cantidad e historial de recogida y el estado cambia a Entregada.
CA-04.2: datos incompletos, actores sin permiso o reintentos no generan una segunda entrega ni alteran el registro original; se informa la causa.
Prioridad P2. Dependencias: CMP-03 y modelo de trazabilidad aprobado. DoR: estados, campos y permisos validados por PO y QA. La asignación automática de destino queda fuera del núcleo.

[Historia en Jira](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-10)

### SCRUM-11

Como participante autorizado, quiero consultar el estado de una donación para conocer el avance de su recogida y entrega.
Comportamiento: CMP-05. Trazabilidad: HU-10 / RF-12 y RF-09; RNF-02, RNF-04 y RNF-06.
CA-05.1: la consulta por identificador muestra el estado vigente Disponible, Reservada o Entregada y el responsable cuando corresponde, consistente con las transiciones registradas.
CA-05.2: un identificador inexistente o la falta de permiso produce una respuesta clara sin revelar información privada.
Prioridad P2. Dependencias: CMP-01, CMP-03 y CMP-04. DoR: criterios y vocabulario de estados aprobados por PO y QA. Indicadores, alertas y reportes se gestionan fuera del núcleo.

[Historia en Jira](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-11)

## Reglas que requieren validación

- Confirmar roles autorizados para registrar, reservar, cerrar entregas y consultar información personal.
- Acordar unidad de cantidad y zona horaria; guardar valores consistentes y presentarlos con claridad.
- Definir qué ocurre cuando una reserva supera la hora límite. No liberar automáticamente una reserva sin una regla aprobada.
- Definir si el estado de recogida se registra como evento de trazabilidad o como estado adicional.
- Establecer el tratamiento de cancelaciones, correcciones y alimentos no aptos antes de ampliar el flujo.
- Validar el canal de registro asistido para personas con teléfonos básicos. WhatsApp no sustituye por sí mismo la compatibilidad con SMS.

## Evidencia de aceptación

Cada criterio se verifica con datos de prueba, resultado esperado, resultado observado y responsable revisor. El caso de reserva simultánea debe demostrar un solo ganador en el almacenamiento, no únicamente una diferencia visual entre pantallas.
