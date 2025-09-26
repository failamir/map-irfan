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
  maps?: string; // manual Google Maps URL if provided by API
}

export interface City {
  name: string;
  count: number;
}

export interface Region {
  id: string;
  name: string;
  clinic_count: number;
  cities: City[];
}