# Lisar — Spec du dashboard v2 (web)

Référence visuelle : `docs/design/dashboard-v2.png` (capture à ajouter).
Référence de code : `docs/design/dashboard-v2-mockup.html`.

> **Pour Claude Code** : le fichier HTML est une **maquette**, pas du code à copier. Il est écrit dans un format de template propre à un outil de design (`<x-dc>`, `<sc-for>`, `<sc-if>`, `{{…}}`, classe `DCLogic`), avec tous les styles en inline. Utilise-le seulement pour retrouver les valeurs exactes (couleurs, espacements, tailles) et la structure. Implémente avec les composants, le routing et les requêtes GraphQL existants du projet. **Toutes les données de la maquette sont fictives** : attributs, quêtes d'exemple, séances, valeurs d'XP.

Avant de coder, analyse les composants actuels du dashboard et propose un plan. Ensuite, implémente **un widget à la fois**, dans cet ordre : fond et layout → statut → suivi → quêtes → entraînement → habitudes.

---

## 1. Design tokens

À déclarer en variables CSS (ou dans le système de thème du projet), jamais en dur dans les composants.

### Couleurs

| Token | Valeur | Usage |
|---|---|---|
| `--bg` | `#03070d` | Fond de page |
| `--bg-sidebar` | `#050b16` | Fond du menu latéral |
| `--border-sidebar` | `#16324d` | Bordure droite du menu, séparateurs du menu |
| `--card-bg` | `#0a1b30` | Fond des cards |
| `--card-border` | `#1d4a6e` | Bordure des cards |
| `--row-bg` | `#0c2138` | Fond d'une ligne dans une card (quête, habitude, séance) |
| `--row-border` | `#173c5c` | Bordure d'une ligne, séparateurs internes |
| `--row-today-bg` | `#0f2b47` | Fond d'une quête de la section Aujourd'hui |
| `--row-today-border` | `#2c6a94` | Bordure d'une quête de la section Aujourd'hui |
| `--accent` | `#5fd4ff` | Cyan principal : titres de section, valeurs, focus, éléments actifs |
| `--btn-bg` | `#0f2b47` | Fond d'un bouton secondaire |
| `--btn-border` | `#2c6a94` | Bordure d'un bouton secondaire |
| `--btn-primary-bg` | `#123553` | Fond d'un bouton principal (bordure `--accent`) |
| `--text` | `#e6f1ff` | Texte principal |
| `--text-2` | `#9db4cc` | Texte secondaire (méta, objectifs, dates) |
| `--text-done` | `#7d95ad` | Titre d'un élément terminé |
| `--text-off` | `#5f7a94` | Valeur absente (ex. `--:--`) |
| `--chip-bg` / `--chip-text` | `#163552` / `#c9d8e8` | Étiquette de catégorie |
| `--seg-off` | `#163552` | Segment vide d'une barre de progression |
| `--xp` | `#f5b546` | XP, priorité haute, titre du joueur |
| `--xp-border` / `--xp-bg` | `#6b5220` / `#1a1608` | Bordure et fond des éléments ambre |
| `--danger` | `#ff6b78` | Retard |
| `--danger-border` / `--danger-bg` | `#7a2a33` / `#1a0f17` | Bordure et fond d'une quête en retard |

Règle couleur : **le rouge est réservé au retard**, et **l'ambre sert à l'XP et à la priorité haute**. Les textes doivent garder un contraste d'au moins 4.5:1 sur leur fond (les valeurs ci-dessus passent).

### Typographie

- **JetBrains Mono** (400/600/700) : titres de section, valeurs numériques, XP, abréviations des attributs, heures.
- **Inter** (400/500/600) : tout le reste.

| Rôle | Taille / graisse |
|---|---|
| Titre de section `[ QUÊTES ]` | Mono 16 px, 600, `letter-spacing: 3px`, `--accent` |
| Titre de sous-section (`AUJOURD'HUI`, `MAISON`…) | Mono 13 px, 600, `letter-spacing: 2px` |
| Titre d'une ligne (quête, habitude, séance) | Inter 15 px, 500 |
| Méta / texte secondaire | Inter 13 px, `--text-2` |
| Étiquette / chip | Inter 12 px |
| Valeur d'un widget de suivi | Mono 22 px |
| Valeur d'un attribut | Mono 16 px |

Pas de libellés en majuscules espacées ailleurs que dans les titres.

### Espacements et formes

- Espacement entre blocs : 24 px. Entre widgets de suivi : 16 px. Entre lignes dans une card : 8 px.
- Padding des cards : environ 20 px × 22–28 px. Padding des lignes : 10 px × 12–14 px.
- Coins droits (pas de border-radius), sauf les pastilles d'habitude (rondes) et la coche « terminée ».
- **Équerres décoratives** sur chaque card : deux coins de 10 × 10 px en `--accent` 1 px, en haut à gauche et en bas à droite, à 6 px des bords (pseudo-éléments `::before` / `::after`).
- Zones cliquables d'au moins 44 × 44 px (40 × 40 accepté pour les boutons secondaires des habitudes).

---

## 2. Fond et layout

### Fond de page
Quatre couches, de haut en bas :
1. Lueur en haut : `radial-gradient(ellipse 70% 45% at 60% 0%, rgba(24,70,115,.45) 0%, transparent 75%)`.
2. Fondu vers le bas qui masque le quadrillage : `linear-gradient(180deg, rgba(3,7,13,.15) 0%, rgba(3,7,13,.7) 30%, #03070d 60%)`.
3. et 4. Quadrillage : lignes de 1 px en `rgba(95,212,255,.08)` tous les 56 px, horizontales et verticales.

Résultat attendu : le quadrillage est visible en haut et **a complètement disparu dans la moitié basse**.

### Structure
- **Menu latéral** : 220–260 px de large, `position: sticky; top: 0; height: 100vh; overflow-y: auto`. Il reste visible quand on fait défiler la page, et défile lui-même si la fenêtre est trop basse.
  - Haut : logo `LISAR` (Mono 24 px, `letter-spacing: 8px`, `--accent`) et sous-titre « Life is a RPG ».
  - Navigation : Tableau de bord, Quêtes, Planning, Entraînements, Records, Statistiques. Item actif : fond `--btn-bg`, barre gauche de 2 px en `--accent`.
  - Bas (poussé avec `margin-top: auto`, séparé par une bordure) : Messages, Amis, Réglages, Se déconnecter.
- **Contenu** : `max-width: 1160px`, centré, padding 32 px × 40 px. De haut en bas :
  1. Statut (pleine largeur)
  2. Suivi : 4 widgets sur une ligne
  3. Une rangée avec Quêtes (2/3 de la largeur) et, à droite, une colonne (1/3) avec Entraînement puis Habitudes.

**Les titres de section sont en dehors des cards**, sur une ligne au-dessus, avec les boutons d'action de la section alignés à droite.

---

## 3. Widget Statut

Card pleine largeur et **compacte** (padding 12 px × 24 px).

- **Gauche** :
  - Médaillon de niveau : carré de 46 px tourné à 45°, bordure `--accent`, fond `--btn-bg`, avec le numéro de niveau (Mono 20 px, 700) remis droit à l'intérieur.
  - À côté : pseudo (Inter 18 px, 600) et titre du joueur en badge ambre (ex. « Titre : L'Éveillé »).
  - Dessous : barre d'XP en **25 segments** (8 px de haut, 3 px d'écart ; segment plein `--accent` avec un léger halo, vide `--seg-off`), puis le compteur `340 / 500 XP` en Mono 13 px.
- **Droite** : les **6 attributs**, Force (FOR), Agilité (AGI), Endurance (END), Intelligence (INT), Esprit (ESP) et Volonté (VOL). Chaque attribut est une petite case (bordure `--card-border`, fond `#081629`) avec l'abréviation (Mono 11 px, `--text-2`) au-dessus de la valeur (Mono 16 px). Le nom complet s'affiche en infobulle. **Pas d'icône.**

À ne pas afficher : le niveau à côté du nom (il est déjà dans le médaillon), le rang, la série, et tout texte du type « encore X XP ».

---

## 4. Widgets de suivi

Quatre cards sur une ligne (`flex: 1 1 200px`) : Hydratation, Pas, Sommeil, Poids.

Contenu d'une card :
- Une ligne avec une **grande icône** à gauche (36 px, trait 1.4, `--accent`, léger halo), puis la **valeur à sa droite** (Mono 22 px) avec l'objectif ou la date en dessous (13 px, `--text-2`). **Pas de libellé** du type « Hydratation ». Le nom sert d'`aria-label` à l'icône.
- Une barre de progression, si le widget en a une : **6 segments**, identiques pour l'eau et les pas.
- Un bouton d'action collé en bas de la card :

| Widget | Valeur | Sous-texte | Bouton |
|---|---|---|---|
| Hydratation | `{x} mL` | objectif 1500 mL | `+250 mL` (ajoute 250 mL ; un segment = 250 mL) |
| Pas | `{x} pas` | objectif 5000 | `Saisir mes pas` |
| Sommeil | `Non notée`, ou la note | nuit de {jour} | `Noter ma nuit` (bouton principal) |
| Poids | `{x} kg` | le {date} | `Nouvelle pesée` |

---

## 5. Widget Quêtes (2/3 de la largeur)

**Une seule card contient toutes les quêtes.** Les quêtes sont des tâches uniquement : **jamais d'entraînement dedans**.

- Ligne de titre, hors de la card : `[ QUÊTES ]`, puis à droite deux boutons icône de 44 px, chacun avec une infobulle et un `aria-label` :
  - « Gérer les catégories » (icône dossier) ;
  - « Nouvelle quête » (icône `+`, bouton principal).
- Dans la card :
  - Onglets : `Actives · {n}` et `Terminées`. Onglet actif : texte `--text` et soulignement de 2 px en `--accent`.
  - **Section « Aujourd'hui »** : une catégorie spéciale, toujours en premier.
  - Puis les catégories de l'utilisateur (Maison, Travail, Enfant…).

### Section « Aujourd'hui »
- En-tête au même format qu'une catégorie, mais mis en avant :
  - étoile pleine `--accent` avec halo ;
  - titre `AUJOURD'HUI` en `--accent` avec un léger `text-shadow` ;
  - date du jour et « retards inclus » en `--text-2` ;
  - progression alignée à droite : `{faites} / {total} · +{xp} XP` ;
  - ligne de soulignement en `--accent` (les autres catégories ont une ligne `--row-border`).
- Contenu : les quêtes **datées du jour** et les quêtes **en retard**.
- Les lignes ont le fond `--row-today-bg` et la bordure `--row-today-border`. Une quête en retard a le fond `--danger-bg`, la bordure `--danger-border` et une case à cocher à bordure `--danger`.
- Chaque quête affiche l'étiquette de sa catégorie d'origine (chip `--chip-bg`).

### Catégories
- En-tête cliquable qui replie ou déplie la catégorie. Il contient un chevron (tourné de 90° quand la catégorie est ouverte), le nom en Mono 13 px, et le nombre de quêtes.
- Si des quêtes de la catégorie sont dans « Aujourd'hui », l'en-tête l'indique à droite, en `--accent` 12 px : « +1 dans Aujourd'hui ».
- Contenu : les quêtes sans date, et celles dont la date est future (elles affichent « Échéance {date} »).

### Ligne de quête
- **À gauche, une case à cocher de 44 × 44 px**, qui est l'action principale. Cochée : fond `--accent` et coche foncée. Le titre passe alors en `--text-done`.
- Ensuite : le titre, éventuellement « {n} / {m} sous-quêtes », et une ligne d'étiquettes (catégorie dans Aujourd'hui, « Priorité haute » en ambre, « En retard · {date} » en rouge, échéance).
- À droite : `+{xp} XP` (Mono 13 px, `--xp`).

### Règles
- **Une quête n'apparaît qu'à un seul endroit.**
- Dès qu'une quête est datée du jour ou en retard, elle passe dans « Aujourd'hui » et sort de sa catégorie. L'en-tête de sa catégorie affiche alors « +n dans Aujourd'hui ».
- Une quête qui n'a pas de date, ou dont la date est future, reste dans sa catégorie.
- Valider une quête ajoute son XP à la barre de statut.

---

## 6. Widget Entraînement (colonne de droite, en haut)

- Ligne de titre, hors de la card : `[ ENTRAÎNEMENT ]`, puis à droite un bouton icône calendrier « Voir ma journée » qui ouvre le Planning.
- Une ligne par séance du jour, **triées par heure**. Les séances sans heure vont à la fin.

### Ligne de séance
- **Heure** à gauche, dans une colonne de 48 px (Mono 14 px, `--accent`). Sans horaire : `--:--` en `--text-off`, avec l'infobulle « Sans horaire ».
- Un trait vertical de 1 px en `--row-border`.
- Le nom (Inter 15 px, 500), avec dessous `{durée} · {n} exercices` (13 px, `--text-2`).
- **À droite, un bouton icône ▶** de 44 px : triangle plein `--accent`, fond `--btn-primary-bg`, bordure `--accent`. Il démarre la séance. Infobulle « Démarrer » et `aria-label` « Démarrer : {nom} ».
- Séance terminée : le nom passe en `--text-done` et le bouton est remplacé par une coche ronde pleine `--accent`.
- **Pas d'icône de sport**, pas de groupes musculaires ni de liste d'exercices.

### États vides
- **Repos** : icône lune, « Jour de repos », puis une ligne « Prochaine séance : {jour} · {nom} ».
- **Rien de prévu** : « Aucune séance prévue » et un bouton « Planifier une séance ».

---

## 7. Widget Habitudes (colonne de droite, sous Entraînement)

- Ligne de titre, hors de la card : `[ HABITUDES ]`, puis à droite un bouton icône « Gérer les habitudes ».
- Une ligne par habitude. Elle contient :
  - le nom, avec dessous la fréquence et la série : « Lun, mer, ven · série 4 » ;
  - **à droite, des boutons icône uniquement** (40 px) :
    - **Historique** (calendrier), sur toutes les habitudes ;
    - **Valider aujourd'hui** (check), seulement sur les habitudes **validées à la main** (ex. Lire). Coché : fond `--accent` et coche foncée. Non coché : contour `--accent` ;
    - pour les habitudes **validées automatiquement** (ex. les pas via le podomètre), un indicateur non cliquable à la place du check : icône de synchro, bordure en pointillés, infobulle « Validée automatiquement ».
  - Une grille de 7 colonnes avec les initiales des jours (L M M J V S D). Le jour courant est en `--accent`. Sous chaque initiale, une pastille de 22 px :

| État | Rendu |
|---|---|
| Fait | Disque plein `--accent` |
| Manqué | Cercle à bordure `--danger` avec une petite croix |
| Aujourd'hui (à faire) | Cercle à bordure de 2 px `--accent` avec un halo |
| Prévu | Cercle à bordure 1 px `#3a6a8f` |
| Non prévu (repos) | Petit tiret horizontal `#3a5670` |

- Valider une habitude manuelle remplit la pastille du jour.

---

## 8. Accessibilité

- Utiliser de vrais `<button>` et `<a href>`, jamais de `onClick` sur une `div`.
- Mettre un `aria-label` sur chaque bouton icône, et un `aria-pressed` sur les cases à cocher et les checks.
- Mettre `aria-expanded` sur les en-têtes de catégorie repliables.
- Focus visible : `outline: 2px solid var(--accent); outline-offset: 2px`.
- Barres de progression : `role="img"` avec un `aria-label` (ex. « Expérience : 340 / 500 XP »).
- Icônes décoratives : `aria-hidden="true"`.

## 9. Hors périmètre pour l'instant

- La version mobile, qui sera faite une fois le web validé.
- La timeline de la journée, qui va dans la page Planning.
- Le calcul réel des attributs et de l'XP : il faut brancher les données existantes. Si elles n'existent pas encore, prévoir des emplacements à remplir et le signaler.
