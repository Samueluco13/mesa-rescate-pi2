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
  
**CA-01.1:** Registro exitoso de una donación

* Escenario: Registrar una donación con datos válidos

  Dado que el comercio donante está autorizado para registrar donaciones

  Y ha ingresado el tipo de alimento

  Y ha ingresado una cantidad mayor que cero con su unidad de medida

  Y ha ingresado el lugar de recogida

  Y ha establecido una hora límite posterior a la hora actual

  Cuando solicita registrar la donación

  Entonces el sistema debe guardar la donación

  Y debe asignarle un identificador único

  Y debe establecer su estado como `Disponible`

  Y debe conservar los datos registrados

---
**CA-01.3:** Validación de campos incompletos o invalidos

* Escenario: Rechazar una donación con datos incompletos o inválidos

  Dado que el comercio donante está registrando una donación

  Y ha ingresado los datos de la donación

  Cuando intenta guardar la donación con <campo> <condición>

  Entonces el sistema debe impedir el registro

  Y debe mostrar un mensaje de error específico para el campo

  Y debe conservar los datos válidos ingresados

  Y no debe crear una donación con información inválida

---
**CA-01.4:** Registro asistido por un coordinador autorizado

* Escenario: Registrar una donación a nombre de un comercio mediante asistencia

  Dado que el coordinador está autenticado y autorizado

  Y existe un comercio donante identificado

  Y dispone de los datos mínimos válidos de la donación

  Cuando registra la donación a nombre del comercio

  Entonces el sistema debe guardar la donación asociada al comercio

  Y debe asignarle un identificador único

  Y debe establecer su estado como `Disponible`

  Y debe registrar la identidad del coordinador que realizó la acción

  Y debe registrar el canal utilizado

  Y debe registrar el origen como `Asistido`

---
### CMP-02: Registro de donación por canal alterno (SMS)
* **Origen:** RF-02 / HU-02
* **CA-02.1:** **Dado** que un comercio envía un mensaje de texto con el formato `"DONO: [alimento] [cantidad] [hora]"`, **cuando** el sistema recibe y procesa el mensaje, **entonces** crea automáticamente el registro de la donación en estado `disponible`.
* **CA-02.2:** **Dado** que el registro por SMS fue procesado exitosamente, **cuando** la donación ingresa al sistema, **entonces** el sistema envía una confirmación por SMS al mismo número de origen.

---

## Épica 2: Logística de Recolección

### CMP-03: Notificación y consulta de recolecciones por disponibilidad y zona
* **Origen:** RF-03, RF-05 / HU-03


**CA-03.3:** Consulta de donaciones disponibles

* Escenario: Mostrar las donaciones disponibles y vigentes

  Dado que el voluntario está autenticado y autorizado
  
  Y existen donaciones disponibles y vigentes
  
  Cuando consulta las donaciones
  
  Entonces el sistema debe mostrar las donaciones disponibles y vigentes
  
  Y debe mostrar el tipo de alimento de cada donación
  
  Y debe mostrar la cantidad y su unidad de medida
  
  Y debe mostrar el lugar de recogida
  
  Y debe mostrar la hora límite de recogida

* Escenario: Excluir las donaciones reservadas y entregadas

  Dado que el voluntario está autenticado y autorizado
  
  Y existen donaciones en estado "Reservada" o "Entregada"
  
  Cuando consulta las donaciones disponibles
  
  Entonces el sistema no debe ofrecer las donaciones reservadas
  
  Y no debe ofrecer las donaciones entregadas

---
**CA-03.4:** Diferenciar la ausencia de resultados de un error de conexión

* Escenario: No existen donaciones disponibles
  
  Dado que el voluntario está autenticado y autorizado
  
  Y la consulta se realiza correctamente
  
  Y no existen donaciones disponibles y vigentes
  
  Cuando consulta las donaciones
  
  Entonces el sistema debe informar que no hay donaciones disponibles
  
  Y no debe mostrar un mensaje de error de conexión
  
  Y debe permitir realizar una nueva consulta
  

* Escenario: Ocurre un error de conexión al consultar las donaciones
  
  Dado que el voluntario está autenticado y autorizado
  
  Y ocurre un error de conexión con el servicio de consulta
  
  Cuando consulta las donaciones
  
  Entonces el sistema debe informar que no fue posible cargar las donaciones
  
  Y debe ofrecer la opción de reintentar la consulta
  
  Y no debe presentar datos antiguos como si fueran una confirmación actual de disponibilidad
  
* Escenario: Reintentar la consulta después de un error de conexión
  
  Dado que el voluntario realizó una consulta que falló por un error de conexión
  
  Cuando selecciona la opción de reintentar
  
  Entonces el sistema debe realizar una nueva solicitud de consulta
  
  Y debe mostrar los resultados actuales si la solicitud es exitosa
  
  Y debe informar nuevamente del error si la conexión continúa fallando

---

### CMP-04: Reserva única y prevención de doble asignación
* **Origen:** RF-04 / HU-04

**CA-04.1:** Evitar duplicados al reintentar una reserva

* Escenario: Reservar una donación disponible y vigente

  Dado que el voluntario está autenticado y autorizado
  
  Y existe una donación en estado `Disponible`
  
  Y la donación se encuentra vigente
  
  Cuando el voluntario solicita reservar la donación
  
  Entonces el servidor debe asociar la reserva exclusivamente al voluntario
  
  Y debe cambiar el estado de la donación a `Reservada`
  
  Y la donación debe dejar de ofrecerse a los demás voluntarios

---
**CA-04.2:** Gestionar dos solicitudes de reserva simultáneas

* Escenario: Dos voluntarios intentan reservar la misma donación simultáneamente
  
  Dado que existen dos voluntarios autenticados y autorizados
  
  Y existe una donación disponible y vigente
  
  Cuando ambos voluntarios solicitan reservar la misma donación simultáneamente
  
  Entonces el sistema debe confirmar la reserva a un solo voluntario
  
  Y debe asociar la donación exclusivamente al voluntario ganador
  
  Y debe cambiar el estado de la donación a `Reservada`
  
  Y debe rechazar la solicitud del otro voluntario
  
  Y debe informar al voluntario rechazado que la donación ya no está disponible
  
  Y no debe generar reservas duplicadas para la misma donación
  
--
**CA-04.3:** Evitar duplicados al reintentar una reserva

* Escenario: Reintentar una reserva después de una interrupción de conexión

  Dado que el voluntario solicita reservar una donación disponible y vigente
  
  Y la conexión se interrumpe durante el procesamiento de la solicitud
  
  Cuando el voluntario reintenta la misma solicitud de reserva
  
  Entonces el sistema debe verificar el resultado de la solicitud anterior
  
  Y no debe crear una reserva duplicada
  
  Y debe conservar al voluntario titular de la reserva si esta ya fue confirmada
  
  Y debe informar al voluntario del resultado de la operación

--

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
