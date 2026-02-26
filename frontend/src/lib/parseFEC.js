/**
 * parseFEC - Parse un fichier FEC (Fichier des Écritures Comptables)
 * Détecte automatiquement le séparateur (|, tab, ;)
 * Retourne un tableau d'écritures normalisées
 */
const parseFEC = async (file) =>
  new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (event) => {
      try {
        const lines = event.target?.result.split(/\r?\n/);
        if (lines.length < 2) throw new Error('Le fichier est vide ou corrompu.');

        const header = lines[0];
        const sep = header.includes('|')
          ? '|'
          : header.includes('\t')
            ? '\t'
            : header.includes(';')
              ? ';'
              : null;

        if (!sep) throw new Error('Format FEC non reconnu (séparateur |, tabulation ou ; requis).');

        const cols = header.split(sep).map((c) => c.trim().toLowerCase());

        const findCol = (names) => {
          const idx = cols.findIndex((c) => names.some((n) => c.includes(n.toLowerCase())));
          if (idx === -1) console.warn(`Colonne manquante : ${names[0]}`);
          return idx;
        };

        const map = {
          JournalCode: findCol(['JournalCode', 'journal_code', 'code']),
          EcritureDate: findCol(['EcritureDate', 'date', 'ecrdate']),
          CompteNum: findCol(['CompteNum', 'compte', 'compte_num']),
          CompteLib: findCol(['CompteLib', 'libelle', 'compte_lib']),
          Debit: findCol(['Debit', 'debit_montant']),
          Credit: findCol(['Credit', 'credit_montant']),
          EcritureLib: findCol(['EcritureLib', 'libelle_ecriture', 'ecriturelib']),
        };

        const entries = [];

        const parseAmount = (val) => {
          if (!val) return 0;
          let cleaned = val.replace(/\s/g, '').replace(',', '.');
          return parseFloat(cleaned) || 0;
        };

        for (let i = 1; i < lines.length; i++) {
          const line = lines[i].trim();
          if (!line) continue;

          const fields = line.split(sep);
          const rawDate = (fields[map.EcritureDate] || '').replace(/[-\/]/g, '');
          const year = parseInt(rawDate.substring(0, 4));
          const month = parseInt(rawDate.substring(4, 6));

          if (isNaN(year) || year < 2000 || year > 2100) continue;

          entries.push({
            JournalCode: fields[map.JournalCode] || '',
            JournalLib: '',
            EcritureNum: '',
            EcritureDate: rawDate,
            CompteNum: (fields[map.CompteNum] || '').trim(),
            CompteLib: (fields[map.CompteLib] || '').trim(),
            EcritureLib: (map.EcritureLib >= 0 ? fields[map.EcritureLib] || '' : '').trim(),
            Debit: parseAmount(fields[map.Debit]),
            Credit: parseAmount(fields[map.Credit]),
            PieceRef: '',
            Year: year,
            Month: month,
          });
        }

        if (entries.length === 0) throw new Error('Aucune donnée valide extraite du fichier.');
        resolve(entries);
      } catch (err) {
        reject(err);
      }
    };
    reader.readAsText(file, 'ISO-8859-1');
  });

export default parseFEC;
