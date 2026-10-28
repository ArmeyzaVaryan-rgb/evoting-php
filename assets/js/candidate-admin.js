/* candidate-admin.js: draf misi contoh untuk admin.
   Draf BUKAN pernyataan resmi kandidat: selalu mengisi status "Draf" dan harus disetujui manual. */
(function () {
    'use strict';
    var EV = window.EVoting || (window.EVoting = {});

    var DRAFTS = {
        komunikasi: [
            'Membangun kanal komunikasi dua arah antara pengurus dan anggota melalui forum berkala dan kanal informasi yang aktif.',
            'Menyusun laporan kegiatan dan keuangan secara berkala dan terbuka bagi seluruh anggota.',
            'Mengevaluasi program kerja setiap semester dengan indikator capaian yang jelas.',
            'Menyediakan saluran aspirasi yang mudah diakses dan ditindaklanjuti.'
        ],
        terukur: [
            'Menetapkan program kerja prioritas dengan target, penanggung jawab, dan jadwal yang terukur.',
            'Menjaga keberlanjutan program melalui dokumentasi dan serah terima antarperiode.',
            'Melakukan evaluasi berkala dan menyampaikan hasilnya kepada anggota.',
            'Menjalin kerja sama dengan pihak internal kampus untuk mendukung pelaksanaan program.'
        ],
        potensi: [
            'Menyelenggarakan pelatihan dan pendampingan sesuai minat dan bakat anggota.',
            'Membuka ruang bagi anggota untuk memimpin kegiatan dan mengembangkan kemampuan berorganisasi.',
            'Mendorong prestasi akademik dan nonakademik melalui program mentoring.',
            'Memberi apresiasi atas kontribusi anggota secara adil dan transparan.'
        ],
        kolaborasi: [
            'Memperkuat kolaborasi lintas angkatan melalui kegiatan bersama dan mentoring senior-junior.',
            'Menciptakan lingkungan organisasi yang inklusif, ramah, dan menghargai perbedaan.',
            'Melibatkan seluruh anggota dalam perencanaan kegiatan sejak tahap awal.',
            'Menjalin kemitraan dengan himpunan dan organisasi lain untuk kegiatan bersama.'
        ]
    };

    function apply(select, textarea, status) {
        textarea.value = DRAFTS[select.value].join('\n');
        status.value = 'draft';
        textarea.focus();
    }

    document.addEventListener('change', function (ev) {
        var select = ev.target.closest('[data-draft-select]');
        if (!select || !select.value || !DRAFTS[select.value]) { return; }
        var textarea = document.getElementById(select.dataset.target);
        var status = document.getElementById(select.dataset.status);
        if (!textarea || !status) { return; }

        if (textarea.value.trim() === '') { apply(select, textarea, status); select.value = ''; return; }
        EV.swal({
            icon: 'warning', iconColor: '#CC9933', title: 'Ganti isi misi?',
            text: 'Isi misi saat ini akan diganti dengan draf contoh. Status akan kembali menjadi Draf.',
            showCancelButton: true, confirmButtonText: 'Ya, ganti', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (r.isConfirmed) { apply(select, textarea, status); }
            select.value = '';
        });
    });
})();
