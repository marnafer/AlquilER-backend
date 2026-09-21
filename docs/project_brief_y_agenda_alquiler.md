# Project Brief: Sistema AlquilER

## 1. Resumen Ejecutivo

**AlquilER** es una plataforma integral (Web y Mobile) diseñada para modernizar y formalizar la búsqueda y gestión de alquileres residenciales. El sistema busca desplazar la informalidad de las redes sociales ofreciendo un entorno seguro y centralizado. Su principal valor agregado es un **sistema de calificaciones (scoring)** bidireccional que permite a los buenos inquilinos acceder a futuras propiedades con menores barreras y requisitos de ingreso, generando un ecosistema de confianza.

## 2. Equipo de Trabajo y Roles

* **Mariano Fernandez (Tech Lead / Backend Dev):** Responsable de la arquitectura de la API REST, desarrollo en PHP/MySQL, seguridad (JWT), despliegue en servidores y apoyo en integración de endpoints.
* **Julián Pretz (Frontend Dev / QA / Documentador):** Responsable del desarrollo de interfaces web (React) y móviles (React Native), aseguramiento de calidad (pruebas E2E), y redacción de manuales y documentación técnica.

## 3. Stack Tecnológico

* **Backend:** PHP 8.2+, MySQL 8.0+, Eloquent ORM.
* **Frontend Web:** React.js.
* **Frontend Mobile:** React Native.
* **Infraestructura y Herramientas:** API REST, JWT para autenticación, Swagger/OpenAPI para documentación, Git/GitHub para versionado y releases.

## 4. Alcance y Entregables (MVP para el 10/11)

1. **Propuesta y Plan:** Documento de alcance, riesgos y cronograma.
2. **Repositorios Gestionados:** Control de versiones con convenciones claras, ramas, issues y releases documentados.
3. **MVP Desplegado:** Aplicación ejecutable e integrada (Backend, Web y Mobile) poblada con datos de prueba reales.
4. **Documentación Técnica y de Usuario:** Arquitectura, esquema de datos, seguridad, manual de usuario y documentación de la API.
5. **Cierre y Presentación:** Informe final (métricas, trabajo futuro), Video demostrativo y Defensa ante comité.

---

# Agenda de Trabajo (21 de Septiembre al 10 de Noviembre)

Quedan aproximadamente **7 semanas**. La clave será no esperar a terminar todo el código para empezar a documentar; Julián deberá ir documentando a medida que Mariano cierra módulos de la API, y luego Julián asume el código Front mientras Mariano prepara servidores y despliegues.

### Semana 1: Setup, Planificación y Cierre Backend (21 Sep - 27 Sep)
* **Equipo:** Redacción final de la Propuesta de Proyecto (alcance, riesgos y plan).
* **Mariano:** Finalizar últimos endpoints pendientes del backend (especialmente la lógica de Calificaciones/Reseñas que es el core).
* **Julián:** Inicializar repositorios de Frontend (Web y Mobile) definiendo convenciones de Git, creación de issues y ramas. Setup de librerías base (React y React Native).

### Semana 2: Desarrollo Frontend Web - Core (28 Sep - 04 Oct)
* **Julián:** Desarrollo en React: Pantallas de Autenticación (Login/Registro con JWT), Home con listado de propiedades y filtros de búsqueda.
* **Mariano:** Pruebas de integración API-Web. Configuración de Swagger para que Julián tenga la documentación de endpoints actualizada.

### Semana 3: Frontend Web Final y Setup Mobile (05 Oct - 11 Oct)
* **Julián:** Desarrollo en React: Perfil de usuario, sistema de Reservas y módulo de Calificaciones/Reseñas.
* **Mariano:** Setup de servidor de pruebas (hosting/VPS) y configuración de base de datos de staging. Primer despliegue del backend en la nube.

### Semana 4: Desarrollo Frontend Mobile (12 Oct - 18 Oct)
* **Julián:** Desarrollo en React Native: Autenticación, visualización de propiedades (UI optimizada para móviles) y opciones de reserva.
* **Mariano:** Asistencia en la integración de React Native con la API. Redacción del borrador de la Documentación Técnica (Arquitectura y Seguridad).

### Semana 5: Testing, QA y Despliegue del MVP (19 Oct - 25 Oct)
* **Julián:** Ejecución de pruebas funcionales (QA). Carga masiva de datos de prueba (propiedades falsas, usuarios, reseñas históricas para probar el scoring).
* **Mariano:** Despliegue de las aplicaciones (Web en Vercel/Netlify o similar, Backend asegurado). Corrección de bugs detectados por QA.

### Semana 6: Documentación y Refinamiento (26 Oct - 01 Nov)
* **Julián:** Redacción del Manual de Usuario (con capturas del sistema final). Revisión de la UX/UI.
* **Mariano:** Consolidación de la Documentación Técnica (Data flow, modelo de base de datos) y armado del Informe Final (métricas, dificultades superadas y trabajo futuro).

### Semana 7: Presentación y Entrega (02 Nov - 10 Nov)
* **02 al 05 Nov:** Grabación y edición del Video Demostrativo obligatorio.
* **06 al 08 Nov:** Creación del Release Final en GitHub enlazando el video y toda la documentación. Preparación de las diapositivas para la defensa.
* **09 y 10 Nov:** Ensayos de la presentación oral (demo en vivo) y preparación para preguntas del comité. Cierre total.