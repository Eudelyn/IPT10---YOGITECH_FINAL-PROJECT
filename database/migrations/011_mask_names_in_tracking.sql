-- Tracking timelines are public: stop showing the recipient's full name.
UPDATE shipment_events SET description = 'Parcel delivered to the recipient.'
 WHERE status = 'delivered' AND description LIKE 'Parcel received by %';
