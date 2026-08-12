(function (window) {
    'use strict';

    const text = (value) => {
        const normalized = String(value ?? '').trim();
        return normalized || '-';
    };

    const safeFilename = (value) => text(value)
        .replace(/[\\/:*?"<>|]+/g, '_')
        .replace(/\s+/g, '_');

    function participantLabels(data) {
        const participants = Array.isArray(data.participants) ? data.participants : [];
        if (participants.length) {
            return participants.map((participant) => text(
                participant.label || `${participant.npk || '-'} — ${participant.name || '-'}`,
            ));
        }

        return data.user
            ? [text(data.user.label || `${data.user.npk || '-'} — ${data.user.name || '-'}`)]
            : ['-'];
    }

    function createDocument(data) {
        if (!window.jspdf?.jsPDF) {
            throw new Error('Generator PDF belum tersedia. Muat ulang halaman dan coba kembali.');
        }

        const pdf = new window.jspdf.jsPDF({
            orientation: 'portrait',
            unit: 'mm',
            format: 'a4',
        });
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        const margin = 14;
        const contentWidth = pageWidth - (margin * 2);
        let y = 18;

        const addHeader = () => {
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(13);
            pdf.text('FORMULIR EVALUASI HASIL PELATIHAN', pageWidth / 2, y, { align: 'center' });
            y += 7;
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(9);
            pdf.text(text(data.activity_type), pageWidth / 2, y, { align: 'center' });
            y += 8;
        };

        const ensureSpace = (height) => {
            if (y + height <= pageHeight - 15) return;
            pdf.addPage();
            y = 18;
            addHeader();
        };

        const addSection = (title) => {
            ensureSpace(12);
            pdf.setFillColor(238, 242, 247);
            pdf.roundedRect(margin, y - 4, contentWidth, 9, 1.5, 1.5, 'F');
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(10);
            pdf.text(title, margin + 3, y + 1.5);
            y += 10;
        };

        const addRow = (label, value) => {
            const labelWidth = 48;
            const lines = pdf.splitTextToSize(text(value), contentWidth - labelWidth - 4);
            let offset = 0;

            while (offset < lines.length) {
                ensureSpace(6);
                const availableLines = Math.max(
                    1,
                    Math.floor((pageHeight - 15 - y) / 4.5),
                );
                const chunk = lines.slice(offset, offset + availableLines);

                pdf.setFontSize(9);
                pdf.setFont('helvetica', 'bold');
                pdf.text(offset === 0 ? `${label}:` : `${label} (lanjutan):`, margin, y);
                pdf.setFont('helvetica', 'normal');
                pdf.text(chunk, margin + labelWidth, y);
                y += Math.max(6, (chunk.length * 4.5) + 1);
                offset += chunk.length;

                if (offset < lines.length) {
                    pdf.addPage();
                    y = 18;
                    addHeader();
                }
            }
        };

        addHeader();
        addSection('INFORMASI KEGIATAN');
        addRow('Jenis kegiatan', data.activity_type);
        addRow('Section', data.section);
        addRow('Program', data.program_training_plan || data.program_training);
        addRow('Penyelenggara', data.lembaga);
        addRow(
            data.is_sharing_knowledge ? `Participant (${participantLabels(data).length})` : 'Peserta',
            participantLabels(data).join('\n'),
        );

        addSection('EVALUASI MATERI');
        addRow('Relevansi', data.relevansi);
        addRow('Alasan relevansi', data.alasan_relevansi);
        addRow('Rekomendasi', data.rekomendasi);
        addRow('Alasan rekomendasi', data.alasan_rekomendasi);

        addSection('EVALUASI PENYELENGGARAAN');
        addRow('Kelengkapan materi', data.kelengkapan_materi);
        addRow('Lokasi', data.lokasi);
        addRow('Metode pengajaran', data.metode_pengajaran);
        addRow('Fasilitas', data.fasilitas);
        addRow('Lainnya', data.lainnya_1);

        addSection(data.is_sharing_knowledge ? 'EVALUASI PESERTA SECARA KELOMPOK' : 'EVALUASI PESERTA');
        addRow('Metode evaluasi', data.metode_evaluasi);
        addRow('Minat', data.minat);
        addRow('Daya serap', data.daya_serap);
        addRow('Penerapan', data.penerapan);
        addRow('Lainnya', data.lainnya_2);

        addSection('EFEKTIVITAS');
        addRow('Hasil', data.efektif);
        addRow('Catatan tambahan', data.catatan_tambahan);

        addSection('PENGESAHAN');
        if (!data.is_sharing_knowledge) {
            addRow('Diketahui oleh', data.diketahui || data.user?.label);
        }
        addRow('Dievaluasi oleh', data.dievaluasi);
        addRow('Tanggal evaluasi', data.tgl_pengajuan);

        return pdf;
    }

    window.TrainingEvaluationPdf = {
        download(data) {
            const pdf = createDocument(data);
            const subject = data.program_training_plan
                || data.program_training
                || data.activity_type
                || 'training';
            pdf.save(`Evaluasi_${safeFilename(subject)}.pdf`);
        },
    };
}(window));
