-- Script per creare un utente amministratore
-- Modifica username, email e password secondo le tue esigenze

USE vm_portal;

-- Inserisci un utente admin
-- Password di default: "admin123" (cambiala!)
-- Per generare un nuovo hash, usa lo script PHP create_admin.php

INSERT INTO users (username, password, email, role) 
VALUES (
    'admin',
    '$2a$12$FHyttXPsx0.uLkhS6fCpluXrDgjBHv4k.SfEWXZN0ow/e0jokY6ii', -- password: "admin123"
    'admin@example.com',
    'admin'
);

-- Verifica che l'utente sia stato creato
SELECT id, username, email, role FROM users WHERE role = 'admin';

