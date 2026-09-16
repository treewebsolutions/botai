# Securitate — remedierile din auditul de penetrare

Auditul Bit Sentinel (septembrie 2026) a fost făcut pe `avpsro.eu`, un proiect construit
pe același schelet Yii2 `master/` + `workspace/`. Cele 18 constatări au fost verificate
una câte una în codul de aici. Majoritatea se regăseau identic, iar verificarea a scos la
iveală și câteva probleme pe care auditul nu le-a văzut.

O parte din remedieri sunt portate de pe ramura `security/upload-rce-and-api-authz` din
masteranunturi, care rezolvase deja aceleași constatări pe un cod înrudit. Pentru detaliul
fiecărei modificări, mesajele de commit explică raționamentul.

## Stadiul constatărilor

| Severitate | Constatare | Stare |
|---|---|---|
| Critic | RCE prin upload de fișier | Rezolvat (+3 căi neraportate) |
| Ridicat | Account takeover prin API | Rezolvat |
| Ridicat | XSS stocat | Rezolvat |
| Ridicat | Politică slabă de parole | Rezolvat |
| Ridicat | Parola se schimbă fără cea curentă | Rezolvat |
| Ridicat | Escaladare de privilegii prin roluri | Rezolvat (+vectorul prin definirea rolului) |
| Mediu | Lipsă rate limiting / CAPTCHA | Rezolvat |
| Mediu | Descărcare de backup-uri | Era deja protejat prin RBAC — *de verificat ce roluri au permisiunea* |
| Mediu | Logout nu invalidează tokenul API | Rezolvat |
| Mediu | Biblioteci JS vulnerabile | Bootstrap și jQuery UI fixate; **TinyMCE rămâne de înlocuit** |
| Mediu | HSTS neaplicat | Rezolvat |
| Mediu | Bypass de validare email prin token | Rezolvat odată cu `auth_key` |
| Mediu | IDOR | Rezolvat |
| Mediu | Enumerare email/telefon | Rezolvat |
| Mediu | CSRF dezactivat | Rezolvat |
| Mediu | Secrete randate în HTML | Rezolvat |
| Mediu | CSV formula injection | Rezolvat |
| Scăzut | Clickjacking | Rezolvat |

### Găsite în plus, neraportate de audit

- **Backdoor de autorizare în API.** `$role = Yii::$app->request->bodyParams['bypass'] ? '@' : ''`
  degrada toate regulile RBAC la „orice user autentificat", la cererea clientului.
- **Validatorii de upload nu rulau.** Nimic nu popula `imageFile`/`attachmentFiles`
  înainte de `validate()`, deci regula `file` cădea pe `null` și era sărită prin
  `skipOnEmpty`. Regulile existau, dar erau decorative.
- **Endpoint-ul de upload scria în orice coloană.** `ProfileController::actionUploadFile()`
  gărdea ținta cu `hasAttribute()`, adevărat pentru orice coloană din `user` — inclusiv
  `auth_key` și `password_hash`.
- **Citire și ștergere arbitrară de fișiere** prin descărcarea exportului: calea venea
  dintr-un token `maskToken`, care e ofuscare reversibilă, nu semnătură.
- **XSS în widget-ul de chat**, care rulează pe site-urile clienților.
- **`mod_headers` nu era activat**, ceea ce anula în tăcere orice bloc `<IfModule mod_headers.c>`.

## De făcut la deploy

Fixurile de cod nu anulează expunerea deja produsă:

1. **Rotește toate cheile `auth_key`.** Erau publicate de orice citire din API. Rotația
   deloghează toți clienții API și toate cookie-urile „ține-mă minte".
2. **Rotește secretele din Setări** — parola SMTP, cheile Stripe, cheia Google Maps, cheia
   secretă reCAPTCHA. Erau randate în `value=` pe paginile de setări, cu un buton de
   dezvăluire lângă fiecare.
3. **Verifică ce roluri dețin** `downloadBackup`, `viewUser`, `createUser`, `updateUser`,
   `deleteUser`. Codul le respectă acum; dacă în RBAC sunt atribuite prea larg, controlul
   nu folosește la nimic.
4. **Confirmă după `git pull` că `.htaccess`-urile din `uploads/` au ajuns pe server.**
   Regula din `.gitignore` a fost îngustată tocmai ca să ajungă, dar protecția RCE
   depinde de ele.
5. **Verifică `mod_headers`** pe serverul de producție; fără el, headerele din `.htaccess`
   sunt ignorate în tăcere (cele ale aplicației vin din PHP și nu depind de el).
6. **Configurează `request.trustedHosts`** dacă aplicația stă în spatele unui proxy, load
   balancer sau CDN. Yii întoarce `REMOTE_ADDR` din `getUserIP()` și ignoră
   `X-Forwarded-For` până i se spune ce proxy-uri să creadă — altfel fiecare cerere poartă
   adresa proxy-ului, toți vizitatorii împart un contor, iar primii zece îi blochează pe
   ceilalți. `RateLimit` detectează semnătura asta și renunță la contorul pe adresă în loc
   să pice site-ul, dar e o plasă, nu o rezolvare.

## Schimbări de comportament care pot rupe ceva

| Schimbare | Ce se poate rupe |
|---|---|
| CSRF activ pe master și workspace | Orice POST automatizat care nu trece prin formular |
| Cookie-uri `sameSite=Lax` și `secure` în producție | Nimic pe HTTPS; widgetul embed e scutit de CSRF tocmai de aceea |
| `auth_key` nu mai apare la citiri | Clienți care îl citeau din `GET /users/{id}` în loc de la login |
| Logout rotește `auth_key` | Sesiunea API a aceluiași cont se încheie la logout din web |
| Parola veche cerută la schimbare | Formularele de profil au acum un câmp în plus |
| Politica de parole (min. 10 + complexitate) | Conturile vechi rămân valide; doar schimbările noi sunt verificate |
| Captcha eșuează închis | Un apelant fără widget trebuie să seteze `requireCaptcha = false` |
| Rate limiting pe credențiale | 10 încercări / 15 min pe IP; NAT-ul unui birou contează ca un client |
| Al doilea contor, pe identificator | Un atacator poate bloca un cont cunoscut de la propria resetare; limita e mai generoasă tocmai de aceea |
| Throttling pe widget-ul de chat | 60 de ture / 15 min pe conversație |
| Autentificare doar cu email | Câmpul postat e `LoginForm[email]`, nu `LoginForm[username]` — inclusiv în `POST /api/v1/user/login`. Un singur cont din tot sistemul avea username (`admin`) și are și el email, deci nimeni nu rămâne pe dinafară |
| `username` și `phone` nu mai sunt unice | Două conturi pot împărți un număr de telefon. Nimic nu mai scrie `user.username`; indexul `username_UNIQUE` rămâne în baza de date, inert |
| Resetarea de parolă din workspace nu mai spune dacă adresa există | Răspunsul e identic pentru o adresă cunoscută și una necunoscută, ca pe master |

## Convenții de respectat în cod nou

- **Upload**: regula `file` are nevoie de atributul populat înainte de `validate()`.
  Populează-l în `load()`, altfel regula e decorativă. Folosește `params['image.*']` /
  `params['file.*']`, nu liste hardcodate.
- **Secrete în formulare**: `passwordInput()` randează valoarea. Folosește
  `SecretSettingTrait` + `'value' => ''` în view.
- **Parole**: `common\validators\PasswordValidator`, nu `['string', 'min' => N]`.
- **Text de la utilizator randat ca HTML**: `HtmlSanitizer::richText()`. Text simplu în
  coloane `raw`: `Html::encode()`.
- **Endpoint nou în API**: verifică proprietarul, nu doar id-ul.
- **Acțiune care atribuie roluri**: treci prin `RoleHelper`.
- **Endpoint public care trimite mail sau verifică credențiale**: pune `RateLimit`,
  cu `identityParams => ['email']`.
- **Căutarea unui cont după credențial**: `User::findByEmail()`. Nu adăuga `username`
  sau `phone` în `OR` — sunt coloane descriptive, neunice și neverificate, iar cine
  întreabă decide altfel pe cine loghezi.
- **Markdown randat în widget**: treci prin `sanitizeMarkdownHtml()`, niciodată direct
  `.html(marked.parse(...))`.

## Testare

Zona nu avea acoperire deloc. Suita master a crescut de la 18 la 116 teste.

> **Atenție la `FakeAdminWebUser`**: trece orice verificare RBAC. Un test de autorizare
> scris fără `grantedPermissions = []` trece din motivul greșit.

> Rate limiting-ul contorizează în `FileCache`, deci e per-server. **Un deploy cu mai
> multe noduri web are nevoie de un cache partajat** ca limitarea să conteze.

## Rămase deschise

- **TinyMCE 4.7.13**, afectată de CVE-2026-47759. Vine prin
  `treewebsolutions/yii2-widget-tinymce` pe `dev-main`, iar trecerea de pe 4.x e o
  migrare de editor, nu un patch. Rich text-ul e purificat la ambele capete, ceea ce
  limitează ce poate face un payload, dar biblioteca tot trebuie înlocuită.
- **jQuery UI 1.12.1**, afectată de CVE-2021-41182, -41183, -41184 și CVE-2022-31160.
  Nu poate fi ridicată: `bower-asset/jquery-ui` se oprește la 1.12.1 (de la 1.13 biblioteca
  a trecut pe npm), iar `yiisoft/yii2-jui` — tras tranzitiv de `lajax/yii2-translate-manager`
  — cere explicit `~1.12.1`. Ieșirea înseamnă înlocuirea lui `yii2-jui`, nu un bump de versiune.
- **Backup-urile nu sunt criptate.** Accesul e restrâns la `superAdmin`, dar arhiva
  conține `db.sql` și configurațiile.
- **CSP complet.** Acum se trimite doar `frame-ancestors`. Un CSP cu `script-src` cere
  întâi scoaterea scripturilor inline din view-uri.
- **`auth_key` face două lucruri deodată** — validează cookie-ul „ține-mă minte" și e
  token API. Separarea lor ar fi fixul de fond; deocamdată logout-ul din web încheie și
  sesiunea API.
- **22 din 23 de formulare cu upload** încă nu populează atributul în `load()`. Cele din
  backend sunt accesibile doar cu cont de admin și stau în spatele `.htaccess`-ului.
