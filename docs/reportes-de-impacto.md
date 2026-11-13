# Reportes de impacto (CMP-09)

Con una cuenta de **Coordinación**, abre **Reportes de impacto** en la webapp. Elige **Desde** y **Hasta** y pulsa **Generar reporte**. El periodo incluye ambos días completos en hora de Colombia y se aplica a la fecha efectiva de entrega, no a la de publicación.

- Solo se incluyen entregas completadas. Los datos marcados como demostración o con un título que empieza por «PRUEBA », «[PRUEBA]» o «[LOCAL]» se excluyen por defecto. Activa la casilla para incluirlos en una demostración.
- Los kilos son la suma de cantidades registradas en kg. Porciones, piezas y litros se muestran por separado.
- **Beneficiarios estimados** expresa raciones equivalentes: 1 porción registrada o 0,5 kg equivalen a 1 ración. Se redondea hacia abajo al final. Es un supuesto del proyecto, no un conteo de personas únicas. Piezas y litros no se convierten. Sin kilos ni porciones aparece «Sin datos».
- Las receptoras asignadas se agrupan por cuenta, aunque hayan cambiado de nombre. Los registros históricos manuales se agrupan por nombre normalizado y se identifican como manuales; no se fusionan con una cuenta sin confirmación. Se señalan las entregas que no tienen organización registrada.
- **Descargar PDF** y **Descargar Excel** generan archivos PDF y XLSX desde el mismo resultado visible: periodo, alcance, totales, método y desglose por receptora. Después de cambiar filtros, pulsa **Generar reporte** para aplicar el nuevo periodo.

El reporte es de consulta y no cambia donaciones ni necesita nuevas tablas. El servidor comprueba que la cuenta tiene permiso de Coordinación. El endpoint no publica el reporte a otros roles. La interfaz hereda el tema, los formularios y los controles de la aplicación.

Verificación: ocho casos del reporte en WordPress aislado (roles, validación, límites de Colombia, unidades, estimación, agrupación, registros de prueba y periodo vacío). En el hosting se generó un reporte como Coordinación y se abrieron ambos archivos descargados para comparar periodo y totales con la pantalla. La exportación también se verificó con un nombre con tildes, texto parecido a fórmulas y un PDF de varias páginas.
