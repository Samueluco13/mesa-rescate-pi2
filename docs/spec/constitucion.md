# Constitución del proyecto

## Roles
| Integrante | Rol |
|---|---|
| Sergio Talero Guzmán | UX Designer |
| Roiman Urrego Zúñiga | DevOps Engineer |
| Samuel Sepúlveda Castaño | QA Engineer |
| Samuel Moreno Mancera | Developer |
| Jhonathan Chicaiza Herrera | Product Owner |
| Diego Alejandro Hernández Forero | Developer |


## Convenciones
### Ramas
No se realizarán cambios directamente sobre la rama principal. Cada integrante del equipo deberá trabajar en una rama independiente:
```
origin/<nombre_integrante>
```

### Commits
Los commits seguirán una variante de Conventional Commits, su estructura es:
```
<tipo>(<alcance>): <descripción>
```

**Tipos permitidos**
**`feat:`** Nueva funcionalidad.

**`fix:`** Corrección de errores.

**`refactor:`** Modificación interna sin cambio de comportamiento.

**`docs:`** Cambios en documentación.

**`test:`** Incorporación o modificación de pruebas.
### Pull Requests
Toda modificación que deba incorporarse a main deberá realizarse mediante un Pull Request (PR), el flujo será:
```
Rama del integrante
        │
        ▼
   Desarrollo
        │
        ▼
   Pull Request
        │
        ▼
Revisión del equipo
        │
        ▼
      Merge
        │
        ▼
      main
```
Para los titulos de los PR se utilizará una convención similar a la de los commits:
```
<tipo>: <descripción>
```