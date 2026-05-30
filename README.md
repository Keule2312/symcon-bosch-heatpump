# Bosch Wärmepumpe – IP-Symcon Modul

[![IPS Version](https://img.shields.io/badge/IP--Symcon-6.0+-blue.svg)](https://www.symcon.de)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Vollständige Integration von Bosch / Buderus / Nefit Wärmepumpen über das **EMS-ESP Gateway (bbqkees)** via MQTT in IP-Symcon.

---

## ✨ Features

- **222 Entitäten** aus EMS-ESP (Boiler + Thermostat) vollständig abgebildet
- Sauber strukturierter **Verzeichnisbaum** mit 19 Kategorien
- **Alle schreibbaren Werte** über WebFront steuerbar
- **COP-Berechnung** automatisch aus Energiedaten
- **WebFront Dashboard** mit animiertem Anlagenschema (Kältekreis, Heizkreis, WWK)
- Unterstützt: CS800i, Logatherm WLW, Compress 6000i/7000i, Nefit, Buderus

---

## 📦 Installation über Modulstore

1. In IP-Symcon: **Kerninstanzen → Modulverwaltung → Hinzufügen**
2. URL eingeben:
   ```
   https://github.com/DEIN-GITHUB-NAME/symcon-bosch-heatpump
   ```
3. **Bosch Wärmepumpe (EMS-ESP)** installieren
4. Neue Instanz anlegen → Bosch Wärmepumpe

---

## ⚙️ Voraussetzungen

| Komponente | Version |
|-----------|---------|
| IP-Symcon | 6.0 oder höher |
| EMS-ESP Gateway | 3.x (bbqkees) |
| MQTT Broker | Mosquitto o.ä., in IPS eingerichtet |

---

## 🔧 Konfiguration

### 1. MQTT Broker
- In IPS einen MQTT-Client einrichten (falls noch nicht vorhanden)
- Verbindung zum selben Broker wie EMS-ESP

### 2. Modulinstanz
| Einstellung | Wert |
|------------|------|
| Topic-Präfix | `ems-esp` (Standard) |
| Kessel/WP | ✓ aktivieren |
| Thermostat | ✓ aktivieren |
| Energiedaten + COP | ✓ aktivieren |
| Dashboard | ✓ aktivieren |

### 3. Variablen anlegen
Button **„Variablen anlegen & Verbindung testen"** klicken → alle 222 Variablen werden automatisch angelegt.

---

## 🌐 WebFront Dashboard

Nach Aktivierung erreichbar unter:
```
http://<IPS-IP>:3777/hook/BoschHeatpump/<InstanzID>
```

Features:
- Animiertes Anlagenschema (Kältekreis fließt live)
- Alle Kältekreistemperaturen (TC0–TR7, PL1, PH1)
- Energiebilanz mit Balken (Wärme vs. Strom)
- Betriebszeiten & Starts
- Schnellübersicht Steuerparameter
- Automatische Aktualisierung alle 30 Sekunden

---

## 📂 Verzeichnisbaum in IPS

```
📁 Bosch Wärmepumpe (Instanz)
  📁 Kessel & Wärmepumpe
    📁 Status               (13 Variablen)
    📁 Temperaturen          (8 Variablen)
    📁 Kältekreis           (16 Variablen)
    📁 Kompressor           (10 Variablen)
    📁 Betriebszeiten       (12 Variablen)
    📁 Energie Abgabe        (4 Variablen)
    📁 Energie Verbrauch     (9 Variablen)
    📁 Warmwasser           (35 Variablen)
    📁 Zusatzheizer         (14 Variablen)
    📁 WP Steuerung         (20 Variablen)
    📁 Eingänge              (8 Variablen)
  📁 Thermostat & Bedienung
    📁 System               (11 Variablen)
    📁 PV Integration        (3 Variablen)
    📁 HK1 Grundeinstellungen (9 Variablen)
    📁 HK1 Temperaturen     (18 Variablen)
    📁 HK1 Raumeinfluss      (5 Variablen)
    📁 HK1 Sommer/Winter     (8 Variablen)
    📁 HK1 Optimierung       (8 Variablen)
    📁 WWK Thermostat       (11 Variablen)
```

---

## 💡 Direkt aufrufbare Funktionen

```php
BHP_SetHK1Mode($id, 2);       // 0=Aus, 1=Manuell, 2=Auto
BHP_SetHPMode($id, 0);        // 0=Heizen, 1=Kühlen, 2=Heizen+Kühlen
BHP_SetDHWMode($id, 4);       // 0=Aus, 1=Eco+, 2=Eco, 3=Komfort, 4=Auto
BHP_SetSilentMode($id, 1);    // 0=Aus, 1=Auto, 2=An
BHP_SetFlowTemp($id, 35.0);   // Vorlauf Sollwert °C
BHP_SetDHWTemp($id, 50.0);    // WWK Solltemperatur °C
BHP_DHWOneTimeCharge($id);    // WWK Einmalladung
BHP_SetAbsent($id, true);     // Abwesend-Modus
BHP_RequestUpdate($id);       // Daten jetzt abrufen
```

---

## 📡 MQTT Topics

| Richtung | Topic | Beschreibung |
|---------|-------|-------------|
| Lesen | `ems-esp/boiler` | Kessel / WP Daten |
| Lesen | `ems-esp/thermostat` | Thermostat Daten |
| Schreiben | `ems-esp/boiler/set` | Kessel Steuerung |
| Schreiben | `ems-esp/thermostat/set` | Thermostat Steuerung |

---

## 📄 Lizenz

MIT License – siehe [LICENSE](LICENSE)
