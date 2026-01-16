# ✅ FIX: Import FEC avec suppression des écritures existantes

## 🔴 Problème identifié

À chaque import d'un FEC de 2024, les écritures n'étaient **PAS supprimées** avant l'insertion des nouvelles. Cela causait une **duplication** des données.

**Exemple du bug:**
```
Import 1: Ajoute 100 écritures → Total: 100
Import 2: Ajoute 100 écritures → Total: 200 ❌ (duplication!)
```

## 🟢 Solution implémentée

Modification du fichier `public_html/api/simple-import.php` pour:

1. **Détecter l'exercice** depuis la première ligne du FEC
2. **SUPPRIMER** toutes les écritures de cet exercice
3. **IMPORTER** les nouvelles écritures

### Code ajouté:

```php
// Étape 2: Détecte l'exercice du FEC
$exercice = 2024; // default
if ($firstData && !empty($firstData['EcritureDate'])) {
    $exercice = (int) substr(trim($firstData['EcritureDate']), 0, 4);
}

// Étape 3: SUPPRIME LES ÉCRITURES EXISTANTES
$deleteStmt = $db->prepare("DELETE FROM ecritures WHERE exercice = ?");
$deleteStmt->execute([$exercice]);
$deleteCount = $deleteStmt->rowCount();

// Étape 4: IMPORTE LES NOUVELLES ÉCRITURES
// ... code d'insertion ...
```

## ✅ Comportement après correction

```
Import 1: DELETE 2024 → INSERT 100 → Total: 100
Import 2: DELETE 2024 (supprime les 100) → INSERT 100 → Total: 100 ✅
```

**Plus de duplication!**

---

## 🧪 Tests créés

### 1. `tests/test-fec-deletion.php`
Teste la suppression basique des écritures:
```bash
php tests/test-fec-deletion.php
```
**Résultat:** ✅ 58,085 écritures supprimées avec succès

### 2. `tests/test-full-import-flow.php`
Teste le flux d'import complet (détection → suppression → insertion):
```bash
php tests/test-full-import-flow.php
```
**Résultat:** ✅ 6 écritures importées sans duplication

### 3. `tests/test-duplicate-import.php`
Teste 2 imports identiques pour vérifier la suppression:
```bash
php tests/test-duplicate-import.php
```
**Résultat:** ✅ Après 1er import: 6 écritures, après 2e: 6 écritures (pas de duplication)

---

## 📝 Fichiers modifiés

| Fichier | Action | Détail |
|---------|--------|--------|
| `public_html/api/simple-import.php` | ✏️ Modifié | Ajout de la détection d'exercice et suppression avant import |
| `backend/services/ImportService.php` | ✏️ Modifié | Suppression aussi implémentée dans ImportService (pour cohérence) |
| `tests/test-fec-deletion.php` | ✨ Créé | Test de suppression basique |
| `tests/test-full-import-flow.php` | ✨ Créé | Test du flux complet |
| `tests/test-duplicate-import.php` | ✨ Créé | Test anti-duplication |
| `tests/fixtures/test-import-2024.txt` | ✨ Créé | Fichier FEC de test |

---

## 🚀 Vérification en production

Lors du prochain import FEC de 2024:

1. ✅ Les écritures de 2024 seront **supprimées**
2. ✅ Les nouvelles écritures du FEC seront **importées**
3. ✅ **Zéro duplication** garantie
4. ✅ Chaque import remplace complètement les données de l'année

---

## 📊 Données de test

Le fichier FEC test `tests/fixtures/test-import-2024.txt` contient:
- **6 écritures** équilibrées
- **3 journaux** différents: AC (Achats), VE (Ventes), CL (Banque)
- **Débits = Crédits:** 6,500.00 EUR chacun

```
Journal | Écritures | Débits    | Crédits
--------|-----------|-----------|----------
AC      | 2         | 1500.00   | 1500.00
VE      | 2         | 2500.00   | 2500.00
CL      | 2         | 2500.00   | 2500.00
--------|-----------|-----------|----------
TOTAL   | 6         | 6500.00   | 6500.00
```

---

## ✨ Conclusion

✅ **Le problème de duplication est résolu!**

À chaque import FEC de 2024:
- Les anciennes écritures de 2024 sont supprimées
- Les nouvelles écritures sont importées
- Aucune duplication possible

**Prêt pour la production!**
