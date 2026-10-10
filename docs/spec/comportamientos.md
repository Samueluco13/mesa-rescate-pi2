# Especificación del MVP: Comportamientos del Sistema

## Estados del Sistema
El ciclo de vida de las donaciones en la plataforma comprende los siguientes estados principales:
- **`disponible`**: Donación registrada y publicada por el comercio, pendiente de asignación o reserva.
- **`asignada`**: Donación reservada exclusivamente por un voluntario para su recolección y asignada a una organización receptora.
- **`en_curso`**: Donación recolectada que se encuentra en proceso de traslado hacia la organización receptora.
- **`completada` / `entregada`**: Donación entregada satisfactoriamente en la organización receptora con registro de trazabilidad completo.
- **`en_riesgo`**: Donación en estado disponible o asignada próxima a vencer respecto a su hora límite de recogida.

---

## Épica 1: Gestión de Donaciones

### CMP-01: Registro de donación por interfaz estándar
* **Origen:** RF-01 / HU-01
* **CA-01.1:** **Dado** que soy un comercio registrado en el sistema, **cuando** indico el tipo de alimento, la cantidad aproximada y la hora límite de recogida con el mínimo de pasos posible, **entonces** la donación queda publicada en estado `disponible`.
* **CA-01.2:** **Dado** que una donación es registrada con éxito en estado `disponible`, **cuando** el sistema procesa el registro, **entonces** envía una notificación automática a los voluntarios activos del sector.

### CMP-02: Registro de donación por canal alterno (SMS)
* **Origen:** RF-02 / HU-02
* **CA-02.1:** **Dado** que un comercio envía un mensaje de texto con el formato `"DONO: [alimento] [cantidad] [hora]"`, **cuando** el sistema recibe y procesa el mensaje, **entonces** crea automáticamente el registro de la donación en estado `disponible`.
* **CA-02.2:** **Dado** que el registro por SMS fue procesado exitosamente, **cuando** la donación ingresa al sistema, **entonces** el sistema envía una confirmación por SMS al mismo número de origen.

---

## Épica 2: Logística de Recolección

### CMP-03: Notificación y consulta de recolecciones por disponibilidad y zona
* **Origen:** RF-03, RF-05 / HU-03
* **CA-03.1:** **Dado** que tengo registrada mi disponibilidad horaria y medio de transporte como voluntario, **cuando** abro la lista de recolecciones pendientes, **entonces** el sistema muestra únicamente las donaciones disponibles que coinciden con mi zona geográfica y rango de horario.
* **CA-03.2:** **Dado** que se publica una nueva recolección en mi zona, **cuando** coincide con mi horario, **entonces** el sistema me notifica indicando el punto de recogida, hora límite y cantidad de alimento.

### CMP-04: Reserva única y prevención de doble asignación
* **Origen:** RF-04 / HU-04
* **CA-04.1:** **Dado** que una recolección está en estado `disponible` y sin asignar, **cuando** confirmo que voy a realizarla, **entonces** el sistema actualiza su estado a `asignada`, vincula mi usuario como transportador y la remueve de la lista de pendientes para los demás voluntarios.
* **CA-04.2:** **Dado** que una recolección ya fue confirmada por otro voluntario (`asignada`), **cuando** intento confirmarla simultáneamente, **entonces** el sistema me notifica que la recolección ya no está disponible e impide la duplicidad de asignación.

---

## Épica 3: Asignación a Organizaciones Receptoras

### CMP-05: Gestión de capacidad y condiciones de recepción
* **Origen:** RF-06 / HU-05
* **CA-05.1:** **Dado** que soy una organización receptora registrada, **cuando** actualizo mi capacidad diaria de recepción y mi disponibilidad de refrigeración (con/sin refrigeración), **entonces** el sistema actualiza mi perfil para considerarlo en las futuras sugerencias de asignación.

### CMP-06: Sugerencia inteligente de destino
* **Origen:** RF-07 / HU-06
* **CA-06.1:** **Dado** que existe una donación confirmada sin destino asignado, **cuando** el sistema evalúa las organizaciones receptoras activas, **entonces** sugiere automáticamente el destino considerando: capacidad disponible, compatibilidad de refrigeración requerida y menor tiempo de traslado.
* **CA-06.2:** **Dado** que ninguna organización activa cumple con los criterios de capacidad o refrigeración, **cuando** el sistema realiza la evaluación, **entonces** notifica a la coordinadora logística (Diana) informando que no existe una asignación automática viable.

### CMP-07: Notificación anticipada a organizaciones receptoras
* **Origen:** RF-08 / HU-07
* **CA-07.1:** **Dado** que una donación fue asignada a mi organización receptora, **cuando** se confirma la asignación, **entonces** recibo un aviso anticipado con el tipo de alimento, cantidad y hora estimada de llegada para planear la recepción.

---

## Épica 4: Trazabilidad y Reportes

### CMP-08: Trazabilidad inmutable de la donación
* **Origen:** RF-09 / HU-08
* **CA-08.1:** **Dado** que una donación fue recolectada y entregada en el destino, **cuando** la entrega se marca como completada, **entonces** el sistema registra de forma inmutable el origen (comercio), cantidad, fecha/hora, transportador (voluntario) y destino final.
* **CA-08.2:** **Dado** que un ente regulador o la coordinadora (Marcela) requiere auditar un proceso, **cuando** consulta una donación por su identificador único, **entonces** el sistema muestra la secuencia histórica completa de eventos y estados de dicho alimento.

### CMP-09: Generación y exportación de reportes de impacto
* **Origen:** RF-10, RF-11 / HU-09
* **CA-09.1:** **Dado** que existen entregas completadas en un periodo determinado, **cuando** Marcela solicita el reporte, **entonces** el sistema calcula automáticamente los kilos totales rescatados, el número estimado de beneficiarios y las organizaciones involucradas en ese rango de tiempo.
* **CA-09.2:** **Dado** que el reporte de impacto fue generado en pantalla, **cuando** selecciono `"exportar"`, **entonces** puedo descargar el reporte en formato PDF o Excel conservando la información presentada e incluyendo el periodo correspondiente.

---

## Épica 5: Panel de Gestión General

### CMP-10: Panel de control y monitoreo en tiempo real
* **Origen:** RF-12 / HU-10
* **CA-10.1:** **Dado** que existen donaciones en distintos estados (`disponible`, `asignada`, `en_curso`, `completada`), **cuando** la coordinadora (Marcela) ingresa al panel principal, **entonces** ve agrupadas las donaciones por estado con sus contadores actualizados.
* **CA-10.2:** **Dado** que una donación no asignada está cercana a cumplir su hora límite de recogida, **cuando** ingresa al estado `en_riesgo`, **entonces** el sistema la resalta visualmente en el panel para requerir atención prioritaria de la coordinadora.
