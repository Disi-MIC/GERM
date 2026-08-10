export interface RepartitionRef {
  id: number;
  nom: string;
}

export interface DashboardPersonnel {
  nbPersonnel: number;
  nbServices: number;
  parDirection: Record<string, { M: number; F: number }>;
  parService: Record<string, { M: number; F: number }>;
  directions: RepartitionRef[];
  services: RepartitionRef[];
  filtreDirection: number | null;
  filtreService: number | null;
}
