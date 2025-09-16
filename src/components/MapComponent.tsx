import React from 'react';
import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import { Icon } from 'leaflet';
import { Clinic } from '../types/clinic';
import { MapPin, Phone, Star } from 'lucide-react';
import 'leaflet/dist/leaflet.css';

interface MapComponentProps {
  clinics: Clinic[];
  selectedClinic?: Clinic | null;
  onClinicSelect: (clinic: Clinic) => void;
}

const createCustomIcon = (isSelected: boolean = false) => {
  return new Icon({
    iconUrl: `data:image/svg+xml;base64,${btoa(`
      <svg width="30" height="40" viewBox="0 0 30 40" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M15 0C6.7 0 0 6.7 0 15C0 22.5 15 40 15 40S30 22.5 30 15C30 6.7 23.3 0 15 0Z" fill="${isSelected ? '#f97316' : '#2563eb'}"/>
        <circle cx="15" cy="15" r="8" fill="white"/>
        <path d="M15 10C12.8 10 11 11.8 11 14C11 16.2 12.8 18 15 18C17.2 18 19 16.2 19 14C19 11.8 17.2 10 15 10Z" fill="${isSelected ? '#f97316' : '#2563eb'}"/>
      </svg>
    `)}`,
    iconSize: [30, 40],
    iconAnchor: [15, 40],
    popupAnchor: [0, -40]
  });
};

const MapComponent: React.FC<MapComponentProps> = ({ 
  clinics, 
  selectedClinic, 
  onClinicSelect 
}) => {
  const defaultCenter: [number, number] = [-6.2088, 106.8456];
  const mapCenter = selectedClinic ? selectedClinic.coordinates : defaultCenter;

  return (
    <div className="h-full w-full overflow-hidden">
      <MapContainer
        center={mapCenter}
        zoom={selectedClinic ? 15 : 6}
        className="h-full w-full"
        key={selectedClinic?.id || 'default'}
      >
        <TileLayer
          attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />
        
        {clinics.map((clinic) => (
          <Marker
            key={clinic.id}
            position={clinic.coordinates}
            icon={createCustomIcon(selectedClinic?.id === clinic.id)}
            eventHandlers={{
              click: () => onClinicSelect(clinic)
            }}
          >
            <Popup className="custom-popup">
              <div className="p-2 min-w-[250px]">
                <div className="flex items-center gap-2 mb-2">
                  <MapPin className="w-4 h-4 text-blue-600" />
                  <h3 className="font-semibold text-gray-800">{clinic.name}</h3>
                </div>
                
                <p className="text-sm text-gray-600 mb-2">{clinic.address}</p>
                
                <div className="flex items-center gap-2 mb-2">
                  <Phone className="w-4 h-4 text-green-600" />
                  <span className="text-sm text-gray-700">{clinic.phone}</span>
                </div>
                
                <div className="flex items-center gap-2 mb-3">
                  <Star className="w-4 h-4 text-yellow-500 fill-current" />
                  <span className="text-sm font-medium">{clinic.rating}</span>
                </div>
                
                <div className="flex flex-wrap gap-1">
                  {clinic.services.slice(0, 2).map((service) => (
                    <span
                      key={service}
                      className="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full"
                    >
                      {service}
                    </span>
                  ))}
                  {clinic.services.length > 2 && (
                    <span className="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
                      +{clinic.services.length - 2} lainnya
                    </span>
                  )}
                </div>
              </div>
            </Popup>
          </Marker>
        ))}
      </MapContainer>
    </div>
  );
};

export default MapComponent;