UKMambassador_public
====================
Egen symfony-app som henter ut informasjon fra wordpress, og cacher denne

For å slette cache, slett "lastbuild.txt", så vil symfony bygge ny cache ved neste page load

Husk å slette prod-cache fra CLI ved oppdateringer av kildekode (skal hektes på git auto-pull når dette skjer)
php bin/console cache:clear --env=prod

Oppdateres ikke automatisk ved git push
