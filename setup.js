import { Composio } from "@composio/core";

// Masukkan API Key Anda di sini (ambil dari menu API Keys di dashboard)
const composio = new Composio({ apiKey: "ak_bk97S6vFKf5p8v6N_0HK" });

async function setup() {
  try {
    // 1. Cek konfigurasi (Opsional, hanya untuk memastikan)
    const triggerType = await composio.triggers.getType("GMAIL_NEW_EMAIL");
    console.log("Konfigurasi yang dibutuhkan:", triggerType.config);

    // 2. Buat trigger dengan User ID Anda tanpa config GitHub
    const trigger = await composio.triggers.create(
      "pg-test-883a0946-21b2-42d8-bd30-bac2d5c1f4fe",
      "GMAIL_NEW_EMAIL"
    );
    console.log(`Trigger berhasil dibuat dengan ID: ${trigger.triggerId}`);

    // 3. Subscribe ke event
    // Catatan: Jika Anda sudah mendaftarkan Webhook URL di dashboard, 
    // bagian subscribe ini sebenarnya opsional karena data sudah otomatis dikirim ke webhook URL Anda.
    await composio.triggers.subscribe(
      (data) => {
        console.log("Event received via SDK:", data);
      },
      { triggerId: trigger.triggerId }
    );

  } catch (error) {
    console.error("Terjadi kesalahan:", error);
  }
}

setup();