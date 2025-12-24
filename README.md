# Portale Web per Creazione Macchine Virtuali

Portale web PHP per la gestione e creazione di macchine virtuali tramite Proxmox VE.

## Requisiti

- PHP 7.4 o superiore
- MySQL/MariaDB
- Proxmox VE con API abilitata
- Estensioni PHP: PDO, cURL, OpenSSL

## Installazione

1. **Database**
   ```bash
   mysql -u root -p < sql/schema.sql
   ```

2. **Configurazione**
   Modifica `config.php` con i tuoi parametri Proxmox:
   ```php
   $PROXMOX_HOST = "192.168.1.20";
   $PROXMOX_TOKEN_ID = "root@pam!vmportal";
   $PROXMOX_TOKEN_SECRET = "IL_TUO_TOKEN_SECRET";
   $PROX_NODE = "pve";
   ```

3. **Database**
   Modifica `db.php` con le credenziali del tuo database MySQL.

4. **Template Proxmox**
   Modifica l'array `$templates` in `request_vm.php` con i template disponibili nel tuo Proxmox.

5. **Utente Admin**
   Crea un utente admin direttamente nel database:
   ```sql
   INSERT INTO users (username, password, email, role) 
   VALUES ('admin', '$2y$10$...', 'admin@example.com', 'admin');
   ```
   Oppure registrati normalmente e modifica il ruolo nel database.

## Funzionalità

### Utenti
- Registrazione e login
- Richiesta di nuove VM scegliendo tra 3 tipi:
  - **Bronze**: 1 core, 1GB RAM, 20GB disco
  - **Silver**: 2 core, 4GB RAM, 40GB disco
  - **Gold**: 4 core, 8GB RAM, 80GB disco
- Visualizzazione stato richieste
- Accesso alle credenziali VM create

### Amministratori
- Approvazione/rifiuto richieste VM
- Visualizzazione tutte le richieste
- Creazione automatica VM su Proxmox dopo approvazione

## Struttura File

```
├── admin/
│   ├── index.php          # Pannello amministratore
│   ├── approve.php        # Approva e crea VM
│   └── reject.php         # Rifiuta richiesta
├── proxmox/
│   └── create_vm.php      # Funzioni API Proxmox
├── templates/
│   ├── header.php         # Header comune
│   └── footer.php         # Footer comune
├── config.php             # Configurazione Proxmox
├── db.php                 # Connessione database
├── index.php              # Dashboard utente
├── login.php              # Login
├── register.php           # Registrazione
├── logout.php             # Logout
├── request_vm.php         # Richiesta nuova VM
├── vm_details.php         # Dettagli e credenziali VM
└── sql/
    └── schema.sql         # Schema database
```

## Configurazione Proxmox API Token

1. Accedi a Proxmox Web UI
2. Vai su **Datacenter** → **Permissions** → **API Tokens**
3. Crea un nuovo token con:
   - User: `root@pam`
   - Token ID: `vmportal`
   - Permessi: `VM.Allocate`, `VM.Clone`, `VM.Config.Network`, `VM.Config.Disk`, `VM.PowerMgmt`

## Note

- Le VM vengono create come container LXC
- L'IP viene recuperato automaticamente quando possibile
- Le credenziali includono password root e chiave SSH privata/pubblica
- Le password vengono generate casualmente
- Le chiavi SSH vengono generate automaticamente

## Sicurezza

⚠️ **Importante per produzione:**
- Cambia le password di default
- Abilita HTTPS
- Configura correttamente i permessi del database
- Limita l'accesso al pannello admin
- Valida e sanitizza tutti gli input
- Considera l'integrazione con LDAP per l'autenticazione

## Licenza

Progetto didattico - Uso educativo

