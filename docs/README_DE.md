# Dokumentation des Groupon-Projekts

## 📝 Inhaltsverzeichnis
- [Überblick](#überblick)
- [Funktionen](#funktionen)
- [Verwendete Technologien](#verwendete-technologien)
- [Installation](#installation)
- [Konfiguration](#konfiguration)
- [Tests und Überprüfung](#tests-und-überprüfung)
- [Roadmap](#roadmap)
- [Mitwirkung](#mitwirkung)

## Überblick
Dieses Projekt ist eine von Groupon inspirierte Plattform zur Verwaltung von Gruppenbestellungen, die es Benutzern ermöglicht, ihre Bestellungen zu erstellen, zu verwalten und zu verfolgen. Das System umfasst eine vollständige Administrationsoberfläche und unterstützt mehrere Sprachen.

## Funktionen
- 🔐 Vollständiges Authentifizierungssystem (Anmeldung, Registrierung, Abmeldung)
- 👤 Benutzerprofilverwaltung
- 📦 Erstellung und Verwaltung von Bestellungen
- 🛠️ Administrationsoberfläche
- 🌍 Mehrsprachige Unterstützung
- 🎨 Helles/dunkles Thema
- 📱 Responsive Benutzeroberfläche

## Verwendete Technologien
- PHP
- JavaScript
- HTML5/CSS3
- REST API
- Bootstrap
- JSON-Datenspeicherung

## Installation
1. Repository klonen
2. Webserver konfigurieren (Apache/Nginx)
3. Einstellungen in `includes/config.php` konfigurieren
4. Anwendung starten

## Konfiguration
### Voraussetzungen
- PHP 7.4 oder höher
- Webserver (Apache/Nginx)

### Datenspeicherkonfiguration
1. Stellen Sie sicher, dass die Ordner `data/users`, `data/commandes` und `data/exports` existieren und beschreibbar sind
2. Es wird keine SQL-Datenbank benötigt, alle Daten werden in JSON-Dateien gespeichert

Weitere Details zur Konfiguration finden Sie im [detaillierten Konfigurationshandbuch](CONFIGURATION.md).

## Tests und Überprüfung

Die Anwendung enthält Testskripte, um zu überprüfen, ob kritische Funktionen richtig konfiguriert sind:

### Cloudflare Turnstile Test

Um zu überprüfen, ob der Turnstile Anti-Robot-Schutz korrekt konfiguriert ist:

1. Konfigurieren Sie Turnstile in `includes/config.php` gemäß den Anweisungen im [Konfigurationshandbuch](CONFIGURATION.md#configuration-de-cloudflare-turnstile)
2. Rufen Sie `http://ihre-website/test_turnstile.php` auf
3. Folgen Sie den Anweisungen auf dem Bildschirm, um die Funktionalität zu testen

### SMTP-Konfigurationstest

Um zu überprüfen, ob der E-Mail-Versand korrekt konfiguriert ist:

1. Konfigurieren Sie die SMTP-Parameter in `includes/config.php` gemäß den Anweisungen im [Konfigurationshandbuch](CONFIGURATION.md#configuration-smtp-pour-lenvoi-demails)
2. Ändern Sie `test_email.php`, um Ihre E-Mail-Adresse anzugeben
3. Rufen Sie `http://ihre-website/test_email.php` auf
4. Das Skript versucht, eine Test-E-Mail zu senden und zeigt das Ergebnis an

> **Sicherheitshinweis**: Nach Abschluss der Tests wird empfohlen, diese Testdateien zu löschen oder den Zugriff darauf zu beschränken.

## Roadmap

### Phase 1 - Q1 2025
- [x] Grundlegendes Authentifizierungssystem
- [x] Grundlegende Bestellverwaltung
- [x] Administrationsoberfläche
- [x] Erste mehrsprachige Unterstützung

### Phase 2 - Q2 2025
- [ ] Verbesserte Benutzeroberfläche
- [ ] Benachrichtigungssystem
- [ ] Integration sicherer Zahlungen
- [ ] Erweitertes Suchsystem

### Phase 3 - Q3 2025
- [ ] Mobile Anwendung
- [ ] Öffentliche API
- [ ] Empfehlungssystem
- [ ] Analysen und Dashboards

## Mitwirkung
Beiträge sind willkommen! So können Sie beitragen:

1. Forken Sie das Projekt
2. Erstellen Sie Ihren Feature-Branch
3. Committen Sie Ihre Änderungen
4. Pushen Sie zum Branch
5. Öffnen Sie einen Pull Request

---

[🔙 Zurück zur Sprachauswahl](../README.md) 