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

  Y debe asignarle un identificador único, establecer su estado como `Disponible` y conservar los datos registrados

---
**CA-01.2:** Selección opcional de un punto en el mapa

* Escenario: Registrar una donación con un punto de recogida seleccionado en el mapa
  
  Dado que el comercio donante está autorizado para registrar donaciones
  
  Y ha ingresado todos los datos obligatorios de la donación
  
  Y ha establecido una hora límite posterior a la hora actual
  
  Cuando selecciona un punto geográfico en el mapa
  
  Y solicita registrar la donación
  
  Entonces el sistema debe guardar la donación
  
  Y debe asignarle un identificador único, establecer su estado como `Disponible`, conservar la ubicación geográfica seleccionada
  
  Y debe asociar el punto geográfico con el lugar de recogida registrado

* Escenario: Registrar una donación sin seleccionar un punto geográfico
  
    Dado que el comercio donante está autorizado para registrar donaciones
  
    Y ha ingresado todos los datos obligatorios de la donación
  
    Y ha establecido una hora límite posterior a la hora actual
  
    Y no ha seleccionado ningún punto geográfico en el mapa
  
    Cuando solicita registrar la donación
  
    Entonces el sistema debe guardar la donación
  
    Y debe asignarle un identificador único, debe establecer su estado como `Disponible`, debe conservar el lugar de recogida ingresado
  
    Y no debe exigir la selección de un punto geográfico

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

  Y debe registrar la identidad del coordinador que realizó la acción, el canal utilizado y el origen como `Asistido`

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
  
  Y debe mostrar el tipo de alimento de cada donación, el lugar de recogida, la hora límite de recogida, la cantidad y su unidad de medida

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
  
---
**CA-04.3:** Evitar duplicados al reintentar una reserva

* Escenario: Reintentar una reserva después de una interrupción de conexión

  Dado que el voluntario solicita reservar una donación disponible y vigente
  
  Y la conexión se interrumpe durante el procesamiento de la solicitud
  
  Cuando el voluntario reintenta la misma solicitud de reserva
  
  Entonces el sistema debe verificar el resultado de la solicitud anterior
  
  Y no debe crear una reserva duplicada
  
  Y debe conservar al voluntario titular de la reserva si esta ya fue confirmada
  
  Y debe informar al voluntario del resultado de la operación

---
**CA-04.4:** Visualización del punto de recogida en el mapa

* Escenario: Visualizar el punto de recogida de una donación reservada
  
  Dado que el voluntario está autenticado y autorizado
  
  Y ha reservado una donación que tiene un punto geográfico registrado por el comercio
  
  Cuando solicita visualizar la ubicación de recogida
  
  Entonces el sistema debe desplegar un mapa
  
  Y debe mostrar un marcador en el punto geográfico seleccionado por el comercio
  
  Y la ubicación mostrada debe corresponder a las coordenadas registradas en la donación

---
**CA-04.5:** Abrir la ubicación de recogida en Google Maps

* Escenario: Abrir la ruta de recogida en Google Maps
  
  Dado que el voluntario está autenticado y autorizado
  
  Y ha reservado una donación con un punto geográfico registrado
  
  Y ha seleccionado un medio de transporte en el menú del sistema
  
  Cuando presiona el botón `Abrir ruta en Maps`
  
  Entonces el sistema debe abrir Google Maps
  
  Y debe establecer como destino las coordenadas del punto de recogida, configurar la ruta según el medio de transporte seleccionado por el voluntario
  
  Y debe permitir al voluntario consultar la ruta en la aplicación o página web de Google Maps

---
## Épica 3: Asignación a Organizaciones Receptoras

### CMP-05: Gestión de capacidad y condiciones de recepción
* **Origen:** RF-06 / HU-05

**CA-05.1:** Actualización de capacidad diaria y refrigeración

* Escenario: Actualizar la disponibilidad de recepción de una organización

  Dado que la organización receptora está registrada y autenticada

  Cuando actualiza su capacidad diaria de recepción y su disponibilidad de refrigeración (con/sin refrigeración)

  Entonces el sistema debe actualizar su perfil en la base de datos

  Y debe considerar los nuevos parámetros en las futuras sugerencias de asignación

---

### CMP-06: Sugerencia inteligente de destino
* **Origen:** RF-07 / HU-06

**CA-06.1:** Sugerencia automática de destino basada en criterios logísticos

* Escenario: Sugerir una organización receptora óptima para una donación

  Dado que existe una donación confirmada sin destino asignado

  Cuando el sistema evalúa las organizaciones receptoras activas

  Entonces el sistema debe sugerir automáticamente el destino considerando la capacidad disponible

  Y debe evaluar la compatibilidad de refrigeración requerida

  Y debe priorizar el menor tiempo de traslado

---

**CA-06.2:** Notificación por ausencia de organizaciones viables

* Escenario: Notificar a la coordinación cuando no hay destino viable

  Dado que existe una donación confirmada sin destino asignado

  Y ninguna organización activa cumple con los criterios de capacidad o refrigeración requeridos

  Cuando el sistema realiza la evaluación de destinos

  Entonces el sistema debe notificar a la coordinadora logística (Diana)

  Y debe informar que no existe una asignación automática viable

---

### CMP-07: Notificación anticipada a organizaciones receptoras
* **Origen:** RF-08 / HU-07

**CA-07.1:** Aviso anticipado a la organización receptora asignada

* Escenario: Notificar los detalles de llegada a la organización receptora

  Dado que una donación fue asignada a una organización receptora

  Y se confirma formalmente la asignación

  Cuando la organización receptora recibe la confirmación

  Entonces el sistema debe enviar un aviso anticipado con el tipo de alimento

  Y debe incluir la cantidad exacta de la donación

  Y debe indicar la hora estimada de llegada para planear la recepción

---

## Épica 4: Trazabilidad y Reportes

### CMP-08: Trazabilidad inmutable de la donación
* **Origen:** RF-09 / HU-08

**CA-08.1:** Cierre y trazabilidad de entregas

* Escenario: Cerrar una donación reservada con datos válidos
  
  Dado que existe una donación en estado `Reservada`
  
  Y el responsable está autenticado y autorizado para cerrar la entrega
  
  Y se ha identificado la organización receptora
  
  Y se ha registrado la fecha y hora de recogida
  
  Y se ha registrado la fecha y hora de entrega
  
  Y se ha identificado al responsable del cierre
  
  Y la fecha y hora de recogida no son posteriores a las de entrega
  
  Y las fechas y horas registradas no corresponden a hechos futuros
  
  Cuando el responsable confirma el cierre de la entrega
  
  Entonces el sistema debe cambiar el estado de la donación a `Entregada`
  
  Y debe asociar la entrega con la organización receptora
  
  Y debe conservar la fecha y hora de recogida
  
  Y debe conservar la fecha y hora de entrega
  
  Y debe registrar al responsable del cierre
  
  Y debe conservar el origen y la cantidad original de la donación
  
  Y debe mantener el historial de recogida y entrega
  
  Y debe mantener separado al voluntario transportador del autor del cierre

---
**CA-08.3:** Validación del cierre de entregas

* Escenario: Rechazar un cierre de entrega inválido
* 
  Dado que existe una donación en estado `Reservada`
  
  Y el usuario intenta cerrar la entrega
  
  Cuando la solicitud presenta datos incompletos
  
  Entonces el sistema debe rechazar el cierre
  
  Y debe informar la causa del rechazo
  
  Y no debe cambiar el estado original de la donación
  
  Y debe conservar el registro y el historial originales
  
  Y no debe generar una segunda entrega
  
* Escenario: Reintentar el cierre de una entrega ya confirmada
  
  Dado que una donación ya fue cerrada y se encuentra en estado `Entregada`
  
  Y existe un registro de entrega confirmado
  
  Cuando el usuario reintenta la misma solicitud de cierre
  
  Entonces el sistema debe reconocer que la entrega ya fue registrada
  
  Y debe informar que la entrega ya se encuentra cerrada
  
  Y no debe generar una segunda entrega
  
  Y no debe modificar el responsable ni los datos originales de la entrega
  
  Y debe conservar el historial existente

---
### CMP-09: Generación y exportación de reportes de impacto
* **Origen:** RF-10, RF-11 / HU-09

**CA-09.1:** Cálculo automático de métricas de impacto

* Escenario: Generar reporte de impacto en un periodo determinado

  Dado que existen entregas completadas en un periodo de tiempo determinado

  Y Marcela solicita el reporte de impacto

  Cuando el sistema procesa la solicitud

  Entonces el sistema debe calcular automáticamente los kilos totales rescatados

  Y debe calcular el número estimado de beneficiarios

  Y debe listar las organizaciones involucradas en ese rango de tiempo

---
**CA-09.2:** Exportación del reporte de impacto en formatos soportados

* Escenario: Descargar el reporte generado en pantalla

  Dado que el reporte de impacto fue generado exitosamente en pantalla

  Cuando el usuario selecciona la opción "exportar"

  Entonces el sistema debe permitir descargar el reporte en formato PDF o Excel

  Y debe conservar fielmente la información presentada

  Y debe incluir claramente el periodo correspondiente en el archivo exportado

---


## Épica 5: Panel de Gestión General

### CMP-10: Panel de control y monitoreo en tiempo real
* **Origen:** RF-12 / HU-10
  
**CA-10.3:** Consulta del estado y trazabilidad de donaciones

* Escenario: Consultar una donación por su identificador

  Dado que el usuario está autenticado y autorizado para consultar la donación
  
  Y existe una donación con el identificador indicado
  
  Y la donación tiene el estado vigente <estado>
  
  Cuando el usuario consulta la donación por su identificador
  
  Entonces el sistema debe mostrar el estado vigente <estado>
  
  Y el estado mostrado debe ser consistente con las transiciones registradas
  
  Y debe mostrar el responsable cuando corresponda según el estado de la donación

---
**CA-10.4:** Control de acceso a la consulta de donaciones

* Escenario: Consultar una donación con un identificador inexistente

  Dado que el usuario está autenticado
  
  Y el identificador consultado no corresponde a ninguna donación registrada
  
  Cuando solicita consultar la donación
  
  Entonces el sistema debe informar que no se encontró una donación con ese identificador
  
  Y no debe mostrar información de otras donaciones

---
* Escenario: Consultar una donación sin permisos suficientes

  Dado que existe una donación registrada
  
  Y el usuario no tiene permisos para consultar esa donación
  
  Cuando intenta consultarla mediante su identificador
  
  Entonces el sistema debe rechazar la consulta
  
  Y debe mostrar un mensaje claro de acceso no autorizado
  
  Y no debe revelar el estado, el responsable ni otros datos privados de la donación
  
