<style>
    .swal2-popup.private-booking-detail-popup {
        max-height: calc(100vh - 2rem);
        overflow-x: hidden;
        overflow-y: auto;
        border-radius: 1.25rem;
        padding: 0;
    }
    .private-booking-detail-popup .swal2-html-container {
        margin: 0 !important;
        padding: 0 !important;
    }
    .private-booking-detail-popup .swal2-actions {
        margin: 0;
        width: 100%;
        border-top: 1px solid #e2e8f0;
        padding: 1rem 1.5rem;
    }
    .private-booking-detail-popup .swal2-close {
        color: #fff;
    }
</style>

<script>
window.showPrivateTrainingDetails = function (event) {
    const props = event.extendedProps || {};
    const escapeHtml = value => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    const value = (input, fallback = '—') => escapeHtml(input || fallback);
    const dateFormatter = new Intl.DateTimeFormat('th-TH', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });
    const timeFormatter = new Intl.DateTimeFormat('th-TH', {
        hour: '2-digit', minute: '2-digit', hour12: false
    });
    const statusTone = {
        pending: ['bg-orange-100', 'text-orange-700'],
        awaiting_court: ['bg-purple-100', 'text-purple-700'],
        confirmed: ['bg-emerald-100', 'text-emerald-700']
    }[props.statusKey] || ['bg-slate-100', 'text-slate-700'];
    const dateLabel = event.start ? dateFormatter.format(event.start) : '—';
    const timeLabel = event.start
        ? `${timeFormatter.format(event.start)}${event.end ? `–${timeFormatter.format(event.end)}` : ''} น.`
        : '—';
    const note = escapeHtml(props.note || 'ไม่มีหมายเหตุ').replaceAll('\n', '<br>');

    const detail = (label, content, extra = '') => `
        <div class="rounded-xl border border-slate-200 bg-white p-3 ${extra}">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">${label}</p>
            <p class="mt-1 break-words text-sm font-semibold text-slate-800">${content}</p>
        </div>`;

    Swal.fire({
        width: 650,
        padding: 0,
        showCloseButton: true,
        showConfirmButton: false,
        confirmButtonText: 'ปิด',
        confirmButtonColor: '#f97316',
        customClass: {
            popup: 'private-booking-detail-popup',
            confirmButton: 'rounded-lg px-7 py-2.5 text-sm font-bold'
        },
        html: `
            <div class="bg-slate-900 px-6 pb-5 pt-6 text-left text-white">
                <div class="flex flex-wrap items-start justify-between gap-3 pr-7">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-300">เทรนเนอร์ส่วนตัว</p>
                        <h2 class="mt-1 text-xl font-bold">รายละเอียดการจอง #${value(props.bookingId)}</h2>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-bold ${statusTone.join(' ')}">${value(props.statusLabel, 'ไม่ระบุสถานะ')}</span>
                </div>
                ${props.roleLabel ? `<p class="mt-3 text-sm text-slate-300">${value(props.roleCaption, 'หน้าที่ของคุณ')}: <span class="font-semibold text-white">${value(props.roleLabel)}</span></p>` : ''}
            </div>
            <div class="bg-slate-50 p-5 text-left sm:p-6">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    ${detail('วันที่', value(dateLabel))}
                    ${detail('เวลา', value(timeLabel))}
                    ${detail('ลูกค้า', value(props.customerName))}
                    ${detail('เบอร์ติดต่อ', value(props.customerPhone, 'ไม่ได้ระบุ'))}
                    ${detail('อีเมล', value(props.customerEmail, 'ไม่ได้ระบุ'), 'sm:col-span-2')}
                    ${detail('โค้ช', value(props.coachName))}
                    ${detail('ผู้ช่วยสนาม', value(props.assistantName, 'ไม่ได้ใช้บริการ'))}
                    ${detail('สนาม', value(props.court, 'รอจัดสนาม'), 'sm:col-span-2')}
                    ${detail('แพ็กเกจ', value(props.packageName, 'ไม่พบข้อมูลแพ็กเกจ'), 'sm:col-span-2')}
                    ${detail('หมายเหตุจากลูกค้า', note, 'sm:col-span-2')}
                </div>
            </div>`,
    });
};
</script>
