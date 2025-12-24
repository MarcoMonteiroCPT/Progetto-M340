<?php
// Configurazione Proxmox
// Scegli quale VM usare: px1 (192.168.56.15) o px2 (192.168.56.16)
$PROXMOX_HOST = "192.168.56.15";       // IP di px1 (cambia con px2 se preferisci)
$PROXMOX_TOKEN_ID = "root@pam!vmportal";  // Formato: username@realm!tokenid
$PROXMOX_TOKEN_SECRET = "fc39a693-d5e9-4b30-9177-c337813c7195";  // DA CREARE su Proxmox Web UI
$PROX_NODE = "px1";                   // Nome nodo (px1 o px2)
$VERIFY_SSL = false;                  // false per test locale (true in produzione)

// VMID dei template da clonare (da creare manualmente su Proxmox)
// Questi sono i VMID delle 3 VM template che devi creare su Proxmox
$VM_TEMPLATES = [
    "bronze" => 200,  // VMID del template Bronze
    "silver" => 201,  // VMID del template Silver
    "gold"   => 202   // VMID del template Gold
];
?>