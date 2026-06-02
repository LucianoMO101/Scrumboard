# ScrumBoard — Web Development 2 Eindopdracht

**Student:** Luciano  
**Studentnummer:** 693538
**Vak:** Web Development 2 (EC 4) — Periode 3  
**Docent:** M. de Haan

---

## Over de applicatie

Een scrum-gebaseerde projectmanagement webapplicatie voor teams die gezamenlijk aan projecten werken en taken organiseren in sprints (Kanban-stijl).

**Geïmplementeerde functionaliteiten:**

| Onderdeel | Functionaliteit | Bestand(en) |
|---|---|---|
| **Authenticatie** | Registreren, inloggen, JWT access + refresh token | `UserController.php`, `JwtService.php`, `AuthStore.js` |
| **Rolgebaseerde autorisatie** | Owner / editor / viewer per project, backend checks geven 403 | `ProjectRoleService.php`, alle controllers |
| **Teams** | Aanmaken, uitnodigen, accepteren/weigeren, verwijderen | `TeamController.php`, `TeamStore.js` |
| **Projecten** | CRUD, gekoppeld aan team, gegroepeerd op dashboard | `ProjectController.php`, `ProjectStore.js` |
| **Leden beheer** | Toevoegen, rol wijzigen, verwijderen | `ProjectController.php`, `ProjectMembers.vue` |
| **Sprints** | Aanmaken, starten, afronden, heropenen, bewerken, verwijderen | `SprintController.php`, `SprintBoard.vue` |
| **Taken + Kanban** | CRUD, drag-and-drop tussen kolommen (todo/doing/done), toewijzen aan gebruiker | `TaskController.php`, `KanbanBoard.vue`, `TaskCard.vue` |
| **Dashboard** | Statistieken (actieve sprints, taken in progress/done), uitnodigingen | `Dashboard.vue` |
| **Activiteitenlog** | Wijzigingen per project bijhouden, filterbaar op actie/type + paginatie | `ActivityLogController.php`, `ActivityLog.vue` |
| **Filtering & paginatie** | GET endpoints ondersteunen `?status`, `?action`, `?entity_type`, `?page`, `?limit` | Alle repository-bestanden |
| **MVC + autoloading** | Controllers → Services → Repositories → Models, PSR-4 autoloading | `Backend/app/` |
| **Responsive styling** | Tailwind CSS, werkt op smartphone/tablet/desktop | Alle `.vue` bestanden |

---

## Logingegevens testaccounts

De volgende accounts worden automatisch aangemaakt via het database init-script.

| Email | Wachtwoord | Rol in demo-project |
|---|---|---|
| `luciano@example.com` | `welkom123` | Owner (Scrum Manager MVP) |
| `jan@example.com` | `welkom123` | Editor |
| `maria@example.com` | `welkom123` | Viewer |

> Je kunt ook zelf een nieuw account registreren via de applicatie. Om toegang te krijgen tot het demo-project, kun je inloggen als `luciano@example.com` en het nieuwe account uitnodigen voor het team.

---

## Opstarten (lokaal via Docker)

### Vereisten
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) geïnstalleerd en actief
- [Node.js](https://nodejs.org/) (v18+) geïnstalleerd

### Stap 1 — Backend starten

```bash
cd Backend
docker compose up -d
```

De database wordt **automatisch aangemaakt** via `Backend/sql/developmentdb.sql` (gemount als init-script in Docker).

---

**Probleemoplossing database**

Als de database al eerder is geïnitialiseerd (bijv. zonder de nieuwste wijzigingen in `sql/developmentdb.sql`), dan worden latere aanpassingen in dat SQL-bestand niet automatisch toegepast. Hierdoor kan de oude database-schema nog tabellen missen zoals `team_invitations` of kolommen zoals `team_members.role`, wat precies de fouten veroorzaakt die je docent zag.

Om de database opnieuw te laten initialiseren, verwijder de Docker volume en start de services opnieuw:

```bash
cd Backend
docker compose down -v
docker compose up -d
```

Dit zal de MySQL-volume `mysqldata` verwijderen zodat het init-script `sql/developmentdb.sql` bij het opnieuw opstarten opnieuw wordt uitgevoerd. Gebruik `http://localhost:8080` om via phpMyAdmin de database te controleren.

> **Wacht ~10 seconden** na het starten voordat je de frontend opent, zodat de MySQL-container volledig klaar is.

### Stap 2 — Frontend starten

```bash
cd Frontend
npm install
npm run dev
```

### Stap 3 — Applicatie openen

Ga naar: **[http://localhost:5173](http://localhost:5173)**

De backend API is bereikbaar op: **[http://localhost](http://localhost)** (poort 80 via nginx)

---

## Projectstructuur

```
├── Backend/
│   ├── docker-compose.yml       # Docker configuratie (nginx, PHP, MariaDB, phpMyAdmin)
│   ├── app/
│   │   ├── controllers/         # MVC: Controllers (HTTP verzoeken afhandelen)
│   │   ├── services/            # MVC: Services (businesslogica)
│   │   ├── repositories/        # MVC: Repositories (databasetoegang via PDO)
│   │   ├── models/              # MVC: Models (datastructuren)
│   │   └── public/index.php     # Entrypoint + routing (Bramus Router)
│   └── sql/
│       └── developmentdb.sql    # Database creatiescript (auto-uitgevoerd bij Docker start)
│
└── Frontend/
    ├── src/
    │   ├── components/          # Vue 3 componenten
    │   ├── stores/              # Pinia state management
    │   └── router/index.js      # Vue Router
    └── vite.config.js
```

---

## Technische keuzes

- **Frontend:** Vue 3 (Composition API), Vite, Pinia, Vue Router, Tailwind CSS
- **Backend:** PHP 8, Bramus Router, Firebase JWT, PDO (MariaDB)
- **Auth:** JWT access token (15 min) + refresh token (7 dagen)
- **Database:** MariaDB in Docker, automatisch aangemaakt via init-script
