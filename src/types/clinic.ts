export interface Clinic {
  id: string;
  name: string;
  address: string;
  city: string;
  region: string;
  phone: string;
  coordinates: [number, number];
  services: string[];
  rating: number;
  image: string;
}

export interface Region {
  id: string;
  name: string;
  cities: string[];
}