# La théorie des attachements polymorphiques

## 1. Le concept fondamental

Un attachement est une **relation orientée** entre deux entités. Cette orientation est **non négociable** et **toujours définie** :

> **Qui attache → Qui est attaché**

Le système ne connaît pas de relations "symétriques par défaut". Chaque relation a un sens.

---

## 2. Les acteurs de la relation

| Terme | Rôle | Analogie |
|-------|------|----------|
| **Rattachable** | Le sujet qui **attache** | Le jardinier qui plante un arbre |
| **Target** | L'objet qui est **attaché** | L'arbre qui est planté |

---

## 3. Le jardin du docteur

### Contexte métier

Un docteur travaille dans un hôpital. Il a des spécialités. Il suit des patients.

---

### 3.1 Le docteur et l'hôpital

```php
$doctor = Doctor::find(1);
$hospital = Hospital::find(1);

$doctor->attachTo($hospital, Role::DOCTOR);
```

**Qui attache ?** Le docteur.  
**Qui est attaché ?** L'hôpital.

**Analogies :** Le docteur signe un contrat avec l'hôpital. Le docteur est le sujet actif. L'hôpital est le réceptacle de cette relation.

**Pour récupérer les hôpitaux du docteur :**
```php
$doctor->getTargetsByType(Hospital::class);
// Le docteur récupère ce qu'il a attaché
```

**Pour récupérer les docteurs de l'hôpital :**
```php
$hospital->getRattachablesByType(Doctor::class);
// L'hôpital récupère ce qui s'est attaché à lui
```

---

### 3.2 Le docteur et sa spécialité

```php
$doctor->attachTo($specialty, Role::SPECIALIST);
```

**Qui attache ?** Le docteur.  
**Qui est attaché ?** La spécialité.

**Analogies :** Le docteur ajoute une compétence à son dossier. La spécialité est une qualification qu'il s'attribue.

**Pour récupérer les spécialités du docteur :**
```php
$doctor->getTargetsByType(Specialty::class);
```

**Pour récupérer les docteurs d'une spécialité :**
```php
$specialty->getRattachablesByType(Doctor::class);
```

---

### 3.3 Le patient et le docteur

```php
$patient->attachTo($doctor, Role::PATIENT_OF);
```

**Qui attache ?** Le patient.  
**Qui est attaché ?** Le docteur.

**Analogies :** Le patient choisit un médecin traitant. Le patient est le sujet actif. Le docteur est le destinataire.

**Pour récupérer les docteurs du patient :**
```php
$patient->getTargetsByRole(Role::PATIENT_OF);
```

**Pour récupérer les patients du docteur :**
```php
$doctor->getRattachablesByRole(Role::PATIENT_OF);
```

---

## 4. La règle de navigation

| Si je suis... | Je récupère... | Via... |
|---------------|----------------|--------|
| Le sujet actif (rattachable) | Ce que j'ai attaché | `$model->getTargets()` |
| L'objet passif (target) | Ce qui m'a attaché | `$model->getRattachables()` |

---

## 5. Les accesseurs métier

Un accesseur métier est une méthode qui **traduit l'intention métier** en opération technique.

### Exemple pour le docteur

```php
class Doctor extends Model
{
    use HasRattachments;

    // Accesseur métier
    protected function hospitals(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getTargetsByType(Hospital::class)
        );
    }

    protected function patients(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getRattachablesByRole(Role::PATIENT_OF)
        );
    }
}
```

**Utilisation :**
```php
$hospitals = $doctor->hospitals;  // Les hôpitaux qu'il a attachés
$patients = $doctor->patients;    // Les patients qui se sont attachés à lui
```

---

## 6. La contrainte de direction

**Une relation est créée dans un sens. Elle est lue selon le sens.**

```php
// Création
$doctor->attachTo($hospital, Role::DOCTOR);

// Lecture - Depuis le docteur
$doctor->getTargetsByType(Hospital::class);  // ✅ Retourne l'hôpital

// Lecture - Depuis l'hôpital
$hospital->getRattachablesByType(Doctor::class);  // ✅ Retourne le docteur
```

**Erreur possible :**
```php
$doctor->getRattachablesByType(Hospital::class);  // ❌ Ne retournera rien
// Le docteur n'est pas target de l'hôpital. C'est l'inverse.
```

---

## 7. En résumé

| Principe | Énoncé |
|----------|--------|
| **Orientation** | Toute relation est orientée : `rattachable → target` |
| **Rattachable** | Le sujet qui attache (actif) |
| **Target** | L'objet qui est attaché (passif) |
| **Lecture** | `getTargets()` = ce que j'ai attaché / `getRattachables()` = ce qui m'a attaché |
| **Accesseurs** | Traduisent l'intention métier en opérations techniques |
| **Symétrie** | La symétrie (ex: amitié, mutualisation) est une **décision métier** qui s'implémente en attachant dans les deux sens |

---

## 8. Cas de symétrie

L'amitié est une relation qui doit être symétrique :

```php
class User extends Model
{
    use HasRattachments;

    public function becomeFriendWith(User $friend): void
    {
        // Symétrie explicite
        $this->attachTo($friend, FriendRole::FRIEND);
        $friend->attachTo($this, FriendRole::FRIEND);
    }
}
```

**Pourquoi deux attachements ?** Parce que chaque utilisateur doit être à la fois :
- Le sujet qui attache l'autre
- L'objet qui est attaché par l'autre

L'amitié n'est pas une relation que le système connaît. C'est une **convention métier** que le développeur implémente à l'aide de deux attachements orientés.

---

## 9. Conclusion

> **Le système est un langage de relations orientées.**
>
> - Chaque relation a un sens
> - Le sens est défini par `rattachable → target`
> - La navigation suit le sens
> - La symétrie est une décision métier

L'orientation est un **choix de conception** qui permet :
- Une lecture claire des relations
- Une validation précise des contraintes
- Une traduction naturelle des intentions métier