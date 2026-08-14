import { LicenceLogiciel } from './licence-logiciel.model';
import { ListeValeurRef, Personnel, ServiceRef } from './personnel.model';

export interface MaterielInformatique {
  id?: number;
  numeroInventaire: string;
  type: ListeValeurRef | string;
  marque: string;
  modele: string;
  numeroSerie?: string | null;
  specifications?: string | null;
  dateAcquisition?: string | null;
  /** RH uniquement (groupe api:read:rh côté serveur) — jamais renvoyé par la vue self-service "Mon parc informatique". */
  fournisseur?: string | null;
  dateMiseEnService?: string | null;
  /** Fréquence de maintenance préventive en mois (3/6/12...), null si aucun plan requis. */
  periodiciteMois?: number | null;
  /** Licence précise couvrant l'installation — pas seulement le produit, voir LicenceLogiciel. */
  systemeExploitation?: LicenceLogiciel | string | null;
  suiteBureautique?: LicenceLogiciel | string | null;
  antivirus?: LicenceLogiciel | string | null;
  etat: ListeValeurRef | string;
  /** Dérivé de l'agent affecté quand affecteA est renseigné (voir MaterielInformatique::getService() côté serveur) ; sinon propre au matériel, absent pour un matériel en stock/réformé sans service connu. */
  service: ServiceRef | string | null;
  affecteA?: Personnel | string | null;
  observations?: string | null;
  createdAt?: string;
  hasPhoto?: boolean;
}
