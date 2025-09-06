# Groupon - Aplicación de Pedidos Grupales

## 📝 Tabla de Contenidos
- [Visión general](#visión-general)
- [Características](#características)
- [Tecnologías utilizadas](#tecnologías-utilizadas)
- [Instalación](#instalación)
- [Configuración](#configuración)
- [Pruebas y verificación](#pruebas-y-verificación)
- [Hoja de ruta](#hoja-de-ruta)
- [Contribución](#contribución)

## Visión general
Este proyecto es una plataforma de gestión de pedidos grupales inspirada en Groupon, que permite a los usuarios crear, gestionar y seguir sus pedidos. El sistema incluye una interfaz de administración completa y es compatible con varios idiomas.

## Características
- 🔐 Sistema de autenticación completo (inicio de sesión, registro, cierre de sesión)
- 👤 Gestión de perfiles de usuario
- 📦 Creación y gestión de pedidos
- 🛠️ Interfaz de administración
- 🌍 Soporte multilingüe
- 🎨 Tema claro/oscuro
- 📱 Interfaz responsive

## Tecnologías utilizadas
- PHP
- JavaScript
- HTML5/CSS3
- API REST
- Bootstrap
- MySQL - Base de datos relacional

## Instalación
1. Clonar el repositorio
2. Configurar el servidor web (Apache/Nginx)
3. Configurar los ajustes en `includes/config.php`
4. Lanzar la aplicación

## Configuración
### Requisitos previos
- PHP 7.4 o superior
- Servidor web (Apache/Nginx)

### Configuración de almacenamiento de datos
1. Asegúrese de que las carpetas `data/users`, `data/commandes` y `data/exports` existan y sean escribibles
2. Configure su base de datos MySQL y acceda a install.php para la instalación automática

Para más detalles sobre la configuración, consulte la [guía de configuración detallada](CONFIGURATION.md).

## Pruebas y verificación

La aplicación incluye scripts de prueba para verificar que las funcionalidades críticas estén correctamente configuradas:

### Prueba de Cloudflare Turnstile

Para verificar si la protección anti-robot Turnstile está correctamente configurada:

1. Configure Turnstile en `includes/config.php` según las instrucciones de la [guía de configuración](CONFIGURATION.md#configuration-de-cloudflare-turnstile)
2. Acceda a `http://su-sitio/test_turnstile.php`
3. Siga las instrucciones en pantalla para probar la funcionalidad

### Prueba de configuración SMTP

Para verificar si el envío de correos electrónicos está correctamente configurado:

1. Configure los parámetros SMTP en `includes/config.php` según las instrucciones de la [guía de configuración](CONFIGURATION.md#configuration-smtp-pour-lenvoi-demails)
2. Modifique `test_email.php` para indicar su dirección de correo electrónico
3. Acceda a `http://su-sitio/test_email.php`
4. El script intentará enviar un correo electrónico de prueba y mostrará el resultado

> **Nota de seguridad**: Una vez completadas las pruebas, se recomienda eliminar o restringir el acceso a estos archivos de prueba.

## Hoja de ruta

### Fase 1 - Q1 2025
- [x] Sistema de autenticación básico
- [x] Gestión básica de pedidos
- [x] Interfaz de administración
- [x] Soporte multilingüe inicial

### Fase 2 - Q2 2025
- [ ] Mejora de la interfaz de usuario
- [ ] Sistema de notificaciones
- [ ] Integración de pagos seguros
- [ ] Sistema de búsqueda avanzado

### Fase 3 - Q3 2025
- [ ] Aplicación móvil
- [ ] API pública
- [ ] Sistema de recomendaciones
- [ ] Analíticas y paneles de control

## Contribución
¡Las contribuciones son bienvenidas! Aquí está cómo puede contribuir:

1. Haga un fork del proyecto
2. Cree su rama de características
3. Confirme sus cambios
4. Envíe a la rama
5. Abra una solicitud de extracción

---

[🔙 Volver a la selección de idioma](../README.md) 