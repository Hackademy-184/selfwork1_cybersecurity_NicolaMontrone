# VulnBlog12 - Blog Vulnerabile per Studio Sicurezza

<p align="center">
<img src="https://img.shields.io/badge/OWASP-Top%2010%202021-red" alt="OWASP Top 10 2021">
<img src="https://img.shields.io/badge/Laravel-12.x-orange" alt="Laravel 10">
<img src="https://img.shields.io/badge/Purpose-Educational-blue" alt="Educational Purpose">
</p>

## ⚠️ AVVERTENZA IMPORTANTE

**Questo progetto è intenzionalmente vulnerabile e contiene numerosi bug di sicurezza!**

Questo blog è stato creato **esclusivamente a scopo educativo** per studiare e comprendere i rischi della OWASP Top 10 2021. **NON utilizzare questo codice in produzione o in ambienti reali.**

## 🎯 Scopo del Progetto

VulnBlog12 è un'applicazione web vulnerabile basata su Laravel che serve come laboratorio per:

- **Studiare** le vulnerabilità della OWASP Top 10 2021
- **Sperimentare** tecniche di hacking e penetration testing
- **Implementare** e testare mitigazioni di sicurezza
- **Comprendere** come funzionano gli attacchi web comuni

## 🐛 Vulnerabilità Intenzionali

Questo blog contiene deliberatamente diverse vulnerabilità di sicurezza, incluse ma non limitate a:

- **Injection** (SQL, XSS, Command)
- **Broken Authentication**
- **Sensitive Data Exposure**
- **XML External Entities (XXE)**
- **Broken Access Control**
- **Security Misconfiguration**
- **Cross-Site Scripting (XSS)**
- **Insecure Deserialization**
- **Using Components with Known Vulnerabilities**
- **Insufficient Logging & Monitoring**

## 🚀 Installazione

### Prerequisiti
- PHP 8.1+
- Composer
- MySQL/PostgreSQL
- Node.js (per Vite)

### Setup
```bash
# Clona il repository
git clone <repository-url>
cd vulnBlog12

# Installa le dipendenze PHP
composer install

# Installa le dipendenze Node.js
npm install

# Copia il file di configurazione
cp .env.example .env

# Genera la chiave dell'applicazione
php artisan key:generate

# Configura il database nel file .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=vulnblog12
# DB_USERNAME=root
# DB_PASSWORD=

# Esegui le migrazioni
php artisan migrate

# Popola il database con dati di esempio
php artisan db:seed

# Avvia il server di sviluppo
php artisan serve
```

## 📚 Risorse per lo Studio

- [OWASP Top 10 2021](https://owasp.org/Top10/)
- [OWASP Testing Guide](https://owasp.org/www-project-web-security-testing-guide/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/)

## 🔒 Ambiente Sicuro

**IMPORTANTE**: Utilizza questo progetto solo in un ambiente isolato:

- ✅ Ambiente di sviluppo locale
- ✅ Macchine virtuali isolate
- ✅ Container Docker dedicati
- ❌ Server di produzione
- ❌ Ambienti connessi a reti reali
- ❌ Database con dati sensibili

## 📝 Licenza

Questo progetto è rilasciato sotto licenza MIT. Ricorda che è stato creato esclusivamente a scopo educativo.

## ⚖️ Disclaimer

Gli autori non si assumono alcuna responsabilità per l'uso improprio di questo software. Questo progetto è destinato esclusivamente all'educazione e alla ricerca in sicurezza informatica in ambienti controllati e autorizzati.

---

**🔍 Buon studio della sicurezza informatica!**
# selfwork1_cybersecurity_NicolaMontrone
