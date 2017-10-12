UKMambassador_public
====================
Symfony-app som henter ut informasjon fra wordpress, og cacher denne

## Slette cache
Slett "lastbuild.txt", så vil symfony bygge ny cache ved neste page load

Husk å slette prod-cache fra CLI ved oppdateringer av kildekode (skal hektes på git auto-pull når dette skjer)
php bin/console cache:clear --env=prod

# Setup

#### 1. Kjør composer install
Bruk default-parameters i dev-miljø
#### 2. Opprett API-nøkler i UKMid-admin (UKM Norge-admin, eget menyvalg)
UKMid-nøkkel:
- API-nøkkel: ambassador
- API-secret: test
- Retur-URL: http://ambassador.ukm.dev/dip/login/	
- Token-URL: http://ambassador.ukm.dev/dip/receive/	

#### 3. Sett opp cron-jobb
UKMDesignBundle henter sitemap fra GitHub, men krever at cron-jobb kjører
Det er anbefalt at dette skjer minst daglig, og forårsaker null nedetid
CRON URL: https://ambassador.ukm.dev/cron/designbundle/sync_sitemap/
