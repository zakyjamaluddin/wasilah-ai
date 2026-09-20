import { Composio } from "@composio/core";

// Ganti dengan API Key dari dashboard Composio Anda
const composio = new Composio({ apiKey: "ak_bk97S6vFKf5p8v6N_0HK" });

async function setup() {
  try {
    const trigger = await composio.triggers.create(
      "pg-test-883a0946-21b2-42d8-bd30-bac2d5c1f4fe", // User ID Anda
      "GMAIL_NEW_GMAIL_MESSAGE"
    );
    console.log(`Trigger berhasil dibuat dengan ID: ${trigger.triggerId}`);
  } catch (error) {
    console.error("Terjadi kesalahan:", error);
  }
}

setup();