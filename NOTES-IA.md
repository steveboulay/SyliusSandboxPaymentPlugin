# Journal d'utilisation de l'IA

Ce plugin est développé avec l'aide de Claude (Anthropic). Quand je l'ai commencé, je découvrais Sylius 2, les tests automatisés, la CI et le travail par pull request. L'IA a préparé le socle et la CI, découpé le travail en étapes, relu chaque pull request et expliqué le fonctionnement de Sylius. Elle n'a pas accès à mon poste : elle lit le dépôt public sur GitHub, et je lui colle la sortie de mes commandes.

Ce journal dit, étape par étape, ce qui vient de l'IA, ce qui vient de moi, ce que j'ai corrigé et comment je l'ai vérifié. Contrairement à celui de [planning-gardes](https://gitlab.com/steveboulay/planning-gardes/-/blob/main/NOTES-IA.md), tenu au fil du travail, il a été reconstitué le 25 novembre 2026 à partir de l'historique Git et de mes échanges avec l'IA.

## Vue d'ensemble

| Partie | Écrit par moi | Donné par l'IA |
|---|---|---|
| Signature des webhooks (`src/Core`) | La classe, 7 tests sur 11 | L'aide sur l'expression régulière et l'ordre des contrôles, la liste des cas, 4 tests |
| Moyen de paiement dans l'admin | L'assemblage et les essais dans l'admin | Le formulaire, la déclaration du service, le hook Twig, le template, les traductions, les 3 tests |
| Redirection vers la page simulée | La commande, le fournisseur de réponse HTTP, l'action, le template, 4 tests sur 16 | Le fournisseur de commande, le handler, le câblage des services, 12 tests, dont un que j'avais commencé |
| CI et outillage | Les choix de PHP 8.3 et de PHPStan 2 | `build.yaml` et ses corrections, `make lint`, le filtre des fichiers Markdown, l'extrait de test pour VS Code |
| Documentation | La relecture | Le README, les ADR de `docs/adr`, le premier jet de ce journal |

- **Ce qui est vérifié, et par qui.** Rien n'arrive sur `main` sans pull request ni CI verte : ECS, PHPStan au niveau maximum, lint des templates Twig et des fichiers YAML, PHPUnit, sur Sylius 2.2 et 2.3, avec MySQL et PostgreSQL. J'essaie chaque parcours à la main, dans l'admin et dans la boutique de test. Avant de fusionner une étape, je réponds par écrit aux questions de revue sur mes choix.
- **Ce que l'IA ne pouvait pas faire.** Elle n'a pas pu installer Sylius dans son espace de travail. Elle a fait tourner des morceaux isolés : ma classe de signature, mes tests avec un lanceur de remplacement, des versions volontairement cassées de mes classes. Pour le reste, c'est la CI et mon poste qui font foi.
- **Ses erreurs.** Elle s'est trompée plusieurs fois. Chaque erreur relevée est notée plus bas : un cas de test mal choisi, un nom d'artefact invalide repris du squelette, deux affirmations fausses pendant une revue, une explication fausse sur la CI, une consigne Git dont l'ordre n'était pas assez clair.
- **Limites à connaître.**
  - `verify()` a été écrite avant ses tests : ce n'est pas du TDD.
  - À l'étape 3.1, chaque classe a suivi le cycle test puis code, mais le test et la classe sont partis dans le même commit : l'historique ne montre pas le test en échec.
  - Les ADR ont été rédigés par l'IA à partir des choix réels du plugin, et une partie de leurs justifications vient d'elle.

## 2026-11-02 · Étape 0 : socle et CI

- **Demandé à l'IA :** un projet public qui comble mon manque de tests et de CI, sur un sujet proche des plugins e-commerce ; puis le socle d'un plugin Sylius 2 et une CI qui tourne dessus.
- **Proposé :**
  - le sujet, une passerelle de paiement simulée, choisi après lecture de la documentation de Sylius 2 et du plugin Sylius d'Alma, et le découpage en étapes ;
  - le squelette officiel `Sylius/PluginSkeleton` (branche 2.3) comme point de départ, et les commandes pour le renommer ;
  - le fichier `build.yaml` : Sylius 2.2 et 2.3 avec MySQL 8.4, Sylius 2.3 avec PostgreSQL 17, et pour chacun ECS, PHPStan, PHPUnit et Behat ;
  - Node 22 dans `compose.override.dist.yml`, demandé par les paquets front de Sylius 2.3, et l'arrêt de Dependabot ;
  - le retrait des exemples du squelette et un premier README.
- **Gardé / corrigé :**
  - Mon premier push ne contenait que le squelette d'origine : le renommage était resté sur mon poste, et la CI tournait avec l'ancien `build.yaml`. Corrigé par un second commit.
  - La configuration Behat importait des suites de Sylius qui n'existent plus en 2.3 (`suites.yml`). Import retiré.
  - Le `build.yaml` de l'IA reprenait du squelette un nom d'artefact contenant le `:` de `mysql:8.4`, que GitHub refuse. Corrigé.
  - Les scénarios Behat d'exemple recevaient des erreurs 502 du serveur de test, sur Sylius 2.3 avec MySQL seulement, et pas à chaque passage. Cause non trouvée. Ces scénarios sont partis avec les exemples du squelette, et Behat ne tourne pas tant que `features/` est vide.
  - Mon premier commit est parti d'un poste où mon identité Git n'était pas configurée : il n'est pas relié à mon compte GitHub. Laissé tel quel, pour ne pas réécrire l'historique de `main`.
- **Vérifié par :** la CI, verte sur les trois configurations. `gh run view --log-failed` ne m'affichait rien : j'ai lu les logs des jobs avec `gh api repos/steveboulay/SyliusSandboxPaymentPlugin/actions/jobs/<id>/logs`.
- **Appris :**
  - Le rôle de chaque contrôle : ECS pour le style, PHPStan pour les types, PHPUnit pour les tests, et la validation de `composer.json` et du conteneur Symfony.
  - Lire une exécution de GitHub Actions job par job, et retrouver l'étape qui a échoué.
  - Un renommage fait en local n'existe pas sur GitHub tant qu'il n'est ni commité ni poussé.

## 2026-11-02 au 03 · Étape 1 : signature des webhooks (PR #11)

- **Demandé à l'IA :** le cahier des charges de la classe, un premier test, la liste des cas à couvrir, puis une revue de la pull request. Le soir du 2 novembre, dans une autre conversation, je lui ai aussi demandé de l'aide pour écrire la classe : l'expression régulière qui lit l'en-tête, et l'ordre des contrôles dans `verify()`.
- **Proposé :**
  - l'interface de `WebhookSignature` (`sign()`, `verify()`, en-tête `t=<horodatage>,v1=<signature>`) et son exception ;
  - le premier test, avec une signature attendue calculée à l'avance en PHP et en Python, pour qu'il ne dépende pas de mon code ;
  - la liste des tests, avec pour chacun la ligne à casser pour vérifier qu'il sait échouer ;
  - le code de deux tests à ma demande, et d'un troisième pendant la revue ;
  - trois questions de revue : pourquoi signer l'horodatage, pourquoi `hash_equals()` plutôt que `===`, pourquoi recevoir l'heure en paramètre.
- **Gardé / corrigé :**
  - J'ai écrit la classe complète, `verify()` compris, avant ses tests. Ce n'est donc pas du TDD, et je ne la présente pas comme telle. Pour compenser, chaque test de la liste était accompagné de l'erreur à réintroduire.
  - La revue a relevé :
    - un dossier `WebHook` alors que les classes disaient `Webhook` ;
    - un message d'erreur mal rédigé (`Secret don't be empty`, devenu `The secret must not be empty.`) ;
    - une faute dans le namespace du test (`Wehhook`) ;
    - une constante mal nommée (`PAYLOADFAILED`, devenue `MODIFIED_PAYLOAD`) ;
    - un espace manquant après un cast, signalé par ECS.
  - L'IA s'est trompée dans sa liste : le cas `t=abc,v1=xyz` cumulait deux défauts, et il serait resté refusé même avec une expression régulière cassée. Elle l'a remplacé par deux cas qui n'ont chacun qu'un seul défaut. Elle avait aussi oublié le cas de l'horodatage modifié, ajouté pendant la revue.
  - Mes erreurs Git : des tests écrits mais ni commités ni poussés, et une pull request restée en brouillon, que GitHub refusait de fusionner (`gh pr ready`).
- **Vérifié par :**
  - ECS, PHPStan et PHPUnit en local, la CI, et le rapport de couverture de Xdebug.
  - L'IA a passé mes tests sur douze versions cassées de la classe, et onze ont été détectées. La douzième remplace `hash_equals()` par `===` : aucun test ne peut la voir, puisque le résultat est identique. C'est justement l'objet de la deuxième question de revue.
- **Bilan :** une classe en PHP pur, sans dépendance à Sylius, couverte par 11 méthodes de test, soit 16 cas avec le fournisseur de données.
- **Appris :**
  - `hash_hmac()` et `hash_equals()`. Une comparaison en temps constant ne révèle pas, par son temps de réponse, jusqu'où une signature est correcte.
  - Signer l'horodatage avec le contenu empêche de réutiliser une ancienne signature en changeant seulement `t`. Un rejeu reste possible pendant la fenêtre de 5 minutes.
  - Recevoir l'heure en paramètre rend la classe prévisible, et permet de tester les limites à 300 et 301 secondes.
  - `#[DataProvider]` et `yield` : le même test tourne une fois par cas, et chaque cas est nommé dans les résultats.
  - `expectExceptionMessage()` en plus de `expectException()`. Sans lui, un test peut passer parce que l'exception part pour une autre raison.
  - `expectNotToPerformAssertions()` pour un test qui vérifie seulement qu'aucune exception ne part.
  - La couverture de code dit qu'une ligne a été exécutée, pas qu'un test vérifie son résultat.
  - Git affiche les fichiers de la branche active : des fichiers « disparus » sont souvent sur une autre branche.

### Tests

- `it_signs_a_payload_with_its_timestamp` : écrit par l'IA, avec la signature calculée à l'avance.
- `it_accepts_a_valid_signature`, `it_rejects_a_modified_payload`, `it_rejects_an_expired_timestamp`, `it_accepts_a_timestamp_at_the_tolerance_limit` et `it_rejects_a_timestamp_too_far_in_the_future` : écrits seul, à partir de la liste.
- `it_rejects_a_signature_made_with_another_secret` : code donné par l'IA, à ma demande. Une instance signe avec un autre secret, une seconde vérifie avec le bon.
- `it_rejects_a_malformed_header`, six en-têtes mal formés : code donné par l'IA, à ma demande, après la correction de sa liste.
- `it_rejects_an_empty_secret` et `it_rejects_a_negative_tolerance` : écrits seul. Ils s'appelaient `it_refuses_…` jusqu'au 10 novembre. Le second avait été ajouté à la liste par l'IA le 3 novembre.
- `it_rejects_a_modified_timestamp` : proposé et écrit par l'IA pendant la revue. Il garde une ancienne signature, change seulement `t`, et échoue si l'horodatage n'est plus signé.

## 2026-11-03 · Étape 2 : le moyen de paiement dans l'admin (PR #13)

- **Demandé à l'IA :** le cahier des charges de l'étape, puis, à ma demande, le code du formulaire et de son test.
- **Proposé :**
  - le test d'intégration, qui vérifie que `sandbox` figure parmi les passerelles de Sylius ;
  - la déclaration du service (`config/services/gateway.php`) avec ses deux tags ;
  - les groupes de validation, le hook Twig de l'admin et le template du champ ;
  - les traductions anglaises, puis françaises pendant la revue ;
  - le formulaire `SandboxGatewayConfigurationType` et ses deux tests ;
  - les valeurs à saisir dans l'admin, dont l'option « Use Payum », cochée par défaut, qu'il faut décocher.

  L'IA a repris ce branchement du plugin Stripe officiel et du code source de Sylius. Elle ne pouvait contrôler que la syntaxe des fichiers.
- **Gardé / corrigé :**
  - L'essentiel du code de cette étape vient de l'IA. Mon travail a été de l'assembler, de le faire tourner, de corriger ce qui bloquait, de vérifier le résultat dans l'admin, et de comprendre comment Sylius insère ce formulaire dans le bloc « Gateway ».
  - Mon test d'intégration avait le namespace du formulaire.
  - `make phpunit` lançait les tests en environnement `dev`, où le conteneur de test de Symfony n'existe pas, alors que la CI était en `test`. Le `Makefile` force désormais `ENV=test` pour PHPUnit.
  - La revue a relevé l'absence des traductions françaises et trois fichiers sans retour à la ligne final.
- **Vérifié par :**
  - PHPUnit en local et dans la CI.
  - Dans l'admin : la création d'un moyen de paiement Sandbox, le secret retrouvé après enregistrement, et un secret vide refusé.
  - Mes réponses aux questions de revue : le rôle des deux tags, ce que voit le test d'intégration que le test unitaire ne voit pas, pourquoi décocher « Use Payum ».
- **Appris :**
  - Un tag de service ne fait rien seul : Sylius cherche les services qui portent le sien, ajoute `sandbox` à la liste des passerelles et relie ce type au formulaire.
  - Sylius insère le formulaire de la passerelle sous `gatewayConfig.config`, et un hook Twig décide où l'afficher.
  - Sans groupes de validation, Sylius n'appliquerait jamais la contrainte `NotBlank`.
  - `TypeTestCase` teste un formulaire sans démarrer l'application ; `KernelTestCase` vérifie le branchement dans l'application réelle.
  - Le conteneur Symfony est compilé et mis en cache : il faut un `cache:clear` après un changement de configuration.
  - Sylius chiffre la configuration des passerelles, avec une clé créée par `sylius:payment:generate-key`.

### Tests

- `it_registers_the_sandbox_gateway` : écrit par l'IA, avant le branchement. Il échoue si le tag est retiré.
- `it_keeps_the_submitted_webhook_secret` et `it_requires_a_webhook_secret` : écrits par l'IA, à ma demande. Le second se vérifie en retirant les groupes de validation de la contrainte.

## 2026-11-03 et 09 · Entretien du dépôt (PR #9, #10, #12 et #14 à #18)

- **Demandé à l'IA :** comment corriger ce qui gênait dans le dépôt, et comment le préparer avant d'en envoyer le lien à des recruteurs.
- **Proposé :**
  - PR #9 : soit ajouter un job PHP 8.2 à la CI, soit exiger PHP 8.3 dans `composer.json`. J'ai choisi PHP 8.3, la version de mes conteneurs et de la CI.
  - PR #10 : une correction de la CI et de `phpunit.xml.dist`, pour ne plus lancer deux fois les mêmes tests.
  - PR #12 : la mise à jour vers PHPStan 2, à mon initiative. L'IA a vérifié que Sylius 2.3 l'utilise et a passé ma classe de signature au niveau maximum avant la mise à jour.
  - PR #14 : un extrait de code (snippet) VS Code qui crée une classe de test avec le namespace déduit du chemin.
  - PR #15 : un README qui décrit les fonctionnalités et l'installation, rédigé à partir du code.
  - PR #16 et #17 : ma ligne de copyright dans `LICENSE`, en gardant celle de Sylius, que la licence MIT impose pour le code repris du squelette, et le retrait de la politique de marque de Sylius, qui ne concerne pas mon code.
  - PR #18 : mon nom comme auteur dans `composer.json`.
- **Vérifié par :** la CI sur chaque pull request, et une relecture du README avant publication.
- **Appris :** une petite pull request par sujet ; `git stash` pour mettre de côté un travail en cours ; `git rebase main` pour remettre une branche à jour ; pas d'en-tête de copyright dans chaque fichier, puisque le fichier `LICENSE` et l'historique Git suffisent.

## 2026-11-09 au 10 · Étape 3.1 : redirection vers la page de paiement simulée (PR #19)

- **Demandé à l'IA :**
  - le découpage de l'étape 3 et un exemple complet du cycle test puis code sur la première classe ;
  - au fil de l'étape : le handler et ses tests, deux tests que je ne savais pas écrire, la fin d'un test que j'avais commencé, et un moyen de vérifier automatiquement le contenu du template ;
  - une revue de chaque classe.
- **Proposé :**
  - le découpage de l'étape 3 en quatre pull requests (redirection, page simulée avec choix, statut, scénarios Behat), et une conception simplifiée : ni client HTTP ni table en base, la page simulée étant servie par le plugin ;
  - un exemple complet pour `CapturePaymentRequestCommandProvider` : le test, son échec, la classe, puis le commit ;
  - le handler `CapturePaymentRequestHandler` et ses quatre tests, expliqués ensuite un par un ;
  - le câblage des services (`config/services/payment.php`), repris du plugin Stripe et du code de Sylius. Il utilise le type `Request|RequestConfiguration`, car Sylius 2.2 et 2.3 ne passent pas le même paramètre au fournisseur de réponse HTTP ;
  - le test d'intégration du template, en anglais et en français, et le lint des templates Twig et des fichiers YAML, dans la CI et dans `make lint` ;
  - trois questions de revue : pourquoi rediriger vers une page séparée, pourquoi une 404 pour une demande inconnue, pourquoi le transport `sync://`.
- **Gardé / corrigé :**
  - J'ai écrit la commande `CapturePaymentRequest`, le fournisseur de réponse HTTP, l'action et le template de la page, ainsi que quatre tests. Le fournisseur de commande, le handler, le câblage et les autres tests viennent de l'IA.
  - Intelephense signalait une erreur sur le constructeur de la commande : la propriété `$hash` était déclarée à la fois par le trait de Sylius et par le constructeur. PHP l'accepte. J'ai pris un constructeur classique pour faire disparaître l'alerte.
  - La revue de mes tests du fournisseur de réponse HTTP a trouvé :
    - un test de `supports()` qui ne fixait pas l'état de la demande, et passait donc pour une mauvaise raison ;
    - un même mock reconfiguré quatre fois, alors que seule la première configuration compte, remplacé par le fournisseur de données `otherStates` ;
    - un nom de variable faux.
  - Ma première version du fournisseur ne vérifiait pas l'URL : trois cas de `invalidResponseData` échouaient. J'ai ajouté le contrôle.
  - Le cas de l'URL vide passait pour une mauvaise raison : Symfony lève lui-même une `InvalidArgumentException`, qui hérite de `LogicException`. Le test vérifie maintenant le message.
  - Dans mon test du 404, une variable n'était pas définie, et des assertions placées après l'exception attendue ne s'exécutaient jamais.
  - Ma première version de l'action passait au template la demande de paiement au lieu du paiement.
  - L'application de test traitait les demandes de paiement par une file d'attente Doctrine, et la redirection n'avait pas lieu dans la même requête. Le transport `sync://` est réglé dans `tests/TestApplication/.env` et `.env.test`.
  - La route n'accepte qu'un UUID comme hash, et un test le vérifie.
  - L'IA s'est trompée deux fois pendant la revue. Elle a affirmé qu'un fichier YAML n'avait pas de retour à la ligne final, alors qu'il en avait un. Elle a aussi affirmé que PHPStan refuserait un type `array` sans précision, alors que la configuration du projet l'accepte.
  - Chaque classe a suivi le cycle test puis code, mais le test et la classe sont partis dans le même commit : l'historique ne montre pas le test en échec. À partir de cette pull request, les fusions gardent tous les commits de la branche (voir l'[ADR 0004](docs/adr/0004-merge-pull-requests-with-a-merge-commit.md)).
- **Vérifié par :**
  - ECS, PHPStan, le lint et PHPUnit en local, puis la CI sur les trois configurations.
  - Un paiement passé à la main dans la boutique de test, jusqu'à la page simulée.
  - L'IA a rejoué une partie de mes tests hors de Sylius, avec PHPUnit, Twig et HttpFoundation installés à part, et sur des versions volontairement cassées de mes classes.
  - Mes réponses aux trois questions de revue.
- **Bilan :** une commande, deux fournisseurs, un handler, une action, un template et une route. Cela fait 16 méthodes de test de plus, soit 30 au total, et 41 cas avec les fournisseurs de données.
- **Appris :**
  - Le système Payment Request de Sylius 2 : chaque action de paiement devient une commande envoyée sur un bus Messenger et traitée par un handler. Sylius choisit le fournisseur selon l'action et la passerelle.
  - Une redirection en `303 See Other` vers une page hébergée ailleurs, comme chez un vrai prestataire.
  - Les mocks de PHPUnit :
    - `createMock()` et `method()->willReturn()` ;
    - `expects($this->once())` et `never()` ;
    - une méthode non configurée renvoie une valeur vide ;
    - la première configuration d'une méthode l'emporte.
  - `setUp()`, que PHPUnit appelle avant chaque test.
  - `expectException()` accepte aussi les sous-classes : il faut vérifier le message.
  - `|trans` dans Twig, avec des clés imbriquées et un paramètre `%amount%`, et `sylius_format_money()` pour afficher un montant.
  - `KernelTestCase` pour générer et reconnaître une route, et pour rendre un template dans une langue donnée.
  - Une demande inconnue donne une 404 et non une 500 : l'erreur vient de la requête, pas du serveur.
  - Une alerte de l'éditeur n'est pas forcément une erreur PHP : PHPStan, PHPUnit et la CI font foi.

### Tests

- `it_supports_any_payment_request` et `it_provides_a_capture_command_with_the_payment_request_hash` : écrits par l'IA, comme exemple complet du cycle, et commités tels quels.
- `it_stores_the_absolute_url_of_the_simulated_payment_page`, `it_moves_the_payment_request_to_processing`, `it_does_nothing_when_the_payment_request_is_already_processing` et `it_looks_up_the_payment_request_of_the_command` : écrits par l'IA, à ma demande, avec le handler. Elle me les a ensuite expliqués un par un.
- `it_supports_a_processing_payment_request` : écrit seul. Il ne fixait pas l'état de la demande ; corrigé pendant la revue.
- `it_does_not_support_other_states` : écrit seul. Le fournisseur de données `otherStates` remplace un mock reconfiguré quatre fois.
- `it_redirects_to_the_simulated_payment_page` : écrit par l'IA, à ma demande.
- `it_rejects_response_data_without_a_valid_url` : écrit seul, avec le fournisseur de données `invalidResponseData`. Il a fait ajouter le contrôle de l'URL dans le fournisseur. Le message de l'exception est vérifié depuis la revue. Il s'appelait `it_fails_without_a_valid_url` jusqu'au 10 novembre.
- `it_renders_the_simulated_payment_page` : écrit par l'IA, à ma demande.
- `it_returns_a_404_for_an_unknown_payment_request` : écrit seul, puis corrigé pendant la revue (variable non définie, assertions jamais exécutées).
- `it_renders_the_translated_page`, en anglais et en français : proposé par l'IA, pour vérifier automatiquement le contenu du template.
- `it_generates_the_simulated_payment_page_url`, `it_routes_the_url_to_the_simulated_payment_page_action` et `it_does_not_route_a_hash_that_is_not_a_uuid` : classe de test commencée par moi, complétée par l'IA.

## 2026-11-10 · Revue de cohérence et réglages VS Code (PR #20 et #21)

- **Demandé à l'IA :** une revue complète du code pour la cohérence des noms et l'ordre des déclarations. J'ai ensuite fait toutes les corrections d'un coup, et je lui ai demandé les commandes pour les répartir en commits séparés. Enfin, des conseils pour retrouver mes réglages VS Code sur mon second poste.
- **Proposé :**
  - la liste des incohérences :
    - une exception sans le suffixe `Exception` ;
    - une méthode privée placée avant les méthodes publiques ;
    - une comparaison qui ne suivait pas l'ordre Yoda du standard Sylius ;
    - des noms de tests en `refuses` et en `rejects` ;
    - des messages d'exception non vérifiés ;
    - des restes du squelette ;
    - un README en retard sur le code ;
  - une séquence de `git commit -- <fichiers>`, testée sur une copie de mon dépôt, pour faire cinq commits à partir de modifications non commitées ;
  - `.vscode/extensions.json` pour recommander les extensions du projet, et Settings Sync pour mes réglages personnels.
- **Gardé / corrigé :**
  - J'ai fait toutes les corrections moi-même. Le renommage de l'exception et la mise à jour de son test sont restés dans le même commit, pour que chaque commit passe les tests.
  - La revue finale a trouvé un nom de test que j'avais rendu incomplet en harmonisant (`it_rejects_without_a_valid_url`), et un README qui annonçait encore des fusions en squash. Corrigés avec `git commit --amend` et `git push --force-with-lease`, sur ma branche.
  - J'avais d'abord poussé `extensions.json` avec mes extensions personnelles : Claude Code, GitHub Pull Requests et le pack de langue français. Le fichier ne garde plus que les extensions dont le projet a besoin, EditorConfig et Intelephense.
  - L'IA a d'abord attribué le délai avant le lancement de la CI au push forcé. C'était faux : GitHub met quelques minutes à lancer la CI après n'importe quel push.
- **Vérifié par :** `git status` vide, `git log` avec les cinq commits attendus, et la CI sur chaque pull request.
- **Appris :**
  - `git commit -- <fichiers>` ne commite que les fichiers listés, quel que soit l'état de l'index.
  - `git commit --amend` et `git push --force-with-lease` servent sur ma propre branche, jamais sur `main`.
  - La CI d'une pull request tourne sur un commit de fusion calculé par GitHub (`refs/pull/<n>/merge`).
  - Settings Sync ne synchronise pas les extensions installées côté WSL.

## 2026-11-23 · Décisions d'architecture (PR #22 à #24)

- **Demandé à l'IA :** des ADR (Architecture Decision Records) tirés des vrais choix du plugin, pendant la préparation d'une candidature.
- **Proposé :**
  - quatre ADR rédigés à partir du code, des commits et du README : Payment Request plutôt que Payum, règles métier dans un `src/Core` sans Sylius, signature HMAC-SHA256 avec horodatage, fusion par commit de merge ;
  - un index et un modèle pour les suivants ;
  - la correction du filtre de la CI : `*.md` n'écartait que les fichiers Markdown de la racine, `**/*.md` les écarte tous.
- **Gardé / corrigé :**
  - Les ADR sont publiés tels que l'IA les a rédigés. Les décisions sont les miennes, mais une partie des justifications vient d'elle : la référence au format de Stripe (0003), l'option « les deux systèmes » (0001) et la piste Deptrac pour faire respecter la frontière de `src/Core` (0002).
  - J'ai supprimé la branche `docs/adr` avant d'avoir ouvert et fusionné sa pull request, et le dossier a disparu. J'ai retrouvé les commits avec `git reflog` et recréé la branche. Les commandes de l'IA ne disaient pas assez clairement que le nettoyage devait attendre la fusion.
- **Vérifié par :** la CI sur les pull requests. Les dates et les noms de classes des ADR ont été repris des commits et du code.
- **Appris :**
  - Le format d'un ADR (contexte, options, décision, conséquences), et la règle de ne jamais réécrire un ADR accepté.
  - `git reflog` retrouve les commits d'une branche supprimée, sur le poste où ils ont été faits.
  - Le filtre `paths-ignore` de GitHub Actions, et ce qu'il couvre selon le motif.
