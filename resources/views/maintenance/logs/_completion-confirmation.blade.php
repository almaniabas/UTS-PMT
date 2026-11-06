<script>
document.querySelectorAll('form[data-start]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const start = new Date(form.dataset.start);
        const end = new Date();
        end.setSeconds(0, 0);
        if (Number.isNaN(start.getTime()) || end < start) {
            event.preventDefault();
            window.alert('Waktu mulai tidak valid atau berada setelah waktu sekarang. Periksa tanggal dan jam mulai.');
            return;
        }

        const minutes = Math.floor((end - start) / 60000);
        const duration = `${Math.floor(minutes / 60)} jam ${minutes % 60} menit`;
        const formatTime = (date) => date.toLocaleString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
        const confirmed = window.confirm(
            `Selesaikan perbaikan ini?\n\nMulai: ${formatTime(start)}\nSelesai: ${formatTime(end)}\nDurasi pengerjaan: ${duration}\n\nWaktu selesai akan dicatat saat konfirmasi.`,
        );
        if (!confirmed) event.preventDefault();
    });
});
</script>
