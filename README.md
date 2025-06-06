![Kiwiz](readmedoc/logo_md.png)

Official Kiwiz magento 2 module / Module Kiwiz officiel pour Magento 2

# KIWIZ
Since January 2018, all B2C companies need to certify their deals (invoices and credit memos). Known as the "2018 Anti-fraud TVA Law", the law is practical and controls are currently on going.

Kiwiz is a deal certification solution, entirely independant : you don't need to change your billing tool, Kiwiz connects itself to your online store and keeps all your data in the Blockchain.

Magento, Prestashop, WooCommerce, Drupal... all CMS are compatibles, even your own CMS by connecting it to Kiwiz's API.

---
Depuis Janvier 2018, toutes les sociétés B2C ont l'obligation de certifier leurs transactions (factures et avoirs). Connue sous le nom "Loi anti fraude TVA 2018", la loi est applicable et des contrôles sont déjà en cours.

Kiwiz est une solution de certification des transactions, totalement autonome : nul besoin de changer votre système de facturation, Kiwiz se connecte à votre boutique en ligne et stocke vos données dans la BlockChain.

Magento, Prestashop, WooCommerce, Drupal... tous les CMS sont compatibles, même un CMS propriétaire en se connectant directement à l’API de KIWIZ.

https://www.kiwiz.io

## Subscribe to a Kiwiz solution / Souscrire à une solution Kiwiz
https://www.kiwiz.io/prix

## Compatibility / Compatibilité
- Magento CE 2.3.x
- PHP 7.1.3+ / 7.2.x / 7.3.x

## Command line installation / Installation en ligne de commande
1. ***IMPORTANT*** : Backup your store's database and web directory.
2. Connect to your store via SSH and go to Magento 2 root folder.
3. Run commands:
```
composer require kwz/certification
php bin/magento setup:upgrade
php bin/magento cache:clean
php bin/magento setup:static-content:deploy
```

---
1. ***IMPORTANT*** : Sauvegardez la base de données et le répertoire web de votre site.
2. Connectez-vous à votre site via SSH et accédez au dossier racine de Magento 2.
3. Exécuter les commandes :
```
composer require kwz/certification
php bin/magento setup:upgrade
php bin/magento cache:clean
php bin/magento setup:static-content:deploy
```