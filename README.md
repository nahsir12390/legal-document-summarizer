# Legal Document Summarizer

Laravel app for PDF, DOCX, and TXT summaries using Gemini. Authentication is intentionally absent: history, downloads, retry, and deletion are shared by all visitors. Private storage prevents direct static file access; it does not provide per-user access control.

## Gemini key

1. Open https://aistudio.google.com/apikey and sign in with Google.
2. Create an API key, selecting or creating a Google Cloud project as prompted.
3. Put it in your local `.env` (never in frontend code or source control):

```dotenv
GEMINI_API_KEY=your_key_here
GEMINI_MODEL=gemini-3.5-flash-lite
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=240
```

Official key instructions: https://ai.google.dev/gemini-api/docs/api-key
Model availability: https://ai.google.dev/gemini-api/docs/models

## Setup / upgrade

```bash
composer install
php artisan migrate
php artisan documents:privatize
php artisan config:clear
```

For a fresh install, copy `.env.example` to `.env`, configure the database and key, and run `php artisan key:generate` before migrations. Do not regenerate an existing app key.

`documents:privatize` moves existing public uploads into private storage and removes their public copies. Resolve any reported destination conflicts before continuing.

Run the web server and worker in separate terminals:

```bash
php artisan serve
```

```bash
php artisan queue:work --timeout=180 --tries=2
```

Keep the worker running; uploads display a pending status until a worker processes them. Restart workers after changes to configuration or code using `php artisan queue:restart`. The queue retry interval must exceed the worker timeout. Failed documents have a retry button. The former public `/ai-test` endpoint has been removed.

Extracted text is sent to Google's hosted API. Scanned PDFs require OCR before uploading. Documents over 200,000 characters are rejected rather than truncated. Uploads are limited to 10 MB (PHP upload/post limits must also accommodate that). No live API test is run by the automated tests.

```bash
php artisan test
```

## Free Render deployment for presentations

This repository includes `Dockerfile` and `render.yaml` for one free Docker web service. Apache and a queue worker run together; no separate database or worker service is required. SQLite, uploads, sessions, and queued work are temporary and may disappear on restart, redeployment, or idle shutdown. Upload your presentation documents after opening the site. This setup intentionally keeps your local MySQL `.env` unchanged.

1. Push this project to a GitHub repository. Do not commit `.env`, uploaded documents, or database files.
2. In Render, choose **New → Blueprint**, connect the repository, and select this project's `render.yaml`. If this project is inside a larger repository, set the service root directory to `legal-document-summarizer` and use the corresponding blueprint path.
3. Supply `GEMINI_API_KEY` and `APP_KEY` when prompted. Generate a separate hosting key locally with `php artisan key:generate --show`; copy the complete `base64:...` value into Render's `APP_KEY`. This command does not change your local app key.
4. Confirm the service uses the **Free** plan and deploy. No persistent disk or managed database is needed.
5. Open the generated HTTPS URL. Database creation and migrations happen automatically at startup; the queue worker starts automatically too.

For manual creation instead, choose **New → Web Service**, connect the repository, select **Docker** and **Free**, and enter the environment variables listed in `render.yaml`. Do not add local MySQL credentials. The container sets SQLite paths and uses Render's `PORT` and public URL automatically.

Keep Render's logs open for troubleshooting. Free services may take time to wake after inactivity; open the site before your presentation and then upload the documents you plan to demonstrate. Authentication remains disabled, so visitors share the history and document actions.

Render documentation: https://render.com/docs/free and https://render.com/docs/docker
