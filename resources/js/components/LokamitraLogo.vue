<script setup lang="ts">
// Logo sementara berbentuk SVG + teks.
// Ganti dengan logo asli: taruh file di public/images/logo-lokamitra.png lalu pakai
// <img src="/images/logo-lokamitra.png" alt="Lokamitra" class="h-14" /> di komponen ini.
</script>

<template>
    <div class="flex items-center gap-3" aria-label="Lokamitra">
        <svg viewBox="0 0 48 48" class="size-12" aria-hidden="true">
            <circle cx="24" cy="10" r="4.5" fill="#F5A623" />
            <path
                d="M24 16c-4 3-5 8-3 13 3-1 5-3 6-6 1-3 0-5-3-7Z"
                fill="#F5A623"
            />
            <path d="M8 40c-4-9-1-20 8-25 1 8-1 16-8 25Z" fill="#1F7A4C" />
            <path d="M16 42c2-6 8-9 16-8-2 6-8 9-16 8Z" fill="#7BC043" />
            <path d="M40 40c4-9 1-20-8-25-1 8 1 16 8 25Z" fill="#1F7A4C" />
        </svg>
        <div class="leading-none">
            <p class="text-4xl font-extrabold tracking-tight">
                <span class="text-[#1F5E3F]">Loka</span
                ><span class="text-[#F5A623]">mitra</span>
            </p>
            <p
                class="mt-1 text-[9px] font-medium tracking-wide text-neutral-500"
            >
                Connect &bull; Collaborate &bull; Create Impact
            </p>
        </div>
    </div>
</template>
