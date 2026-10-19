<?php

declare(strict_types=1);

/**
 * Order lifecycle labels. See lang/fr/order.php for the rationale.
 *
 * Arabic agrees with "التسجيل" (masculine), so the statuses are phrased to match
 * it rather than being transliterated from the French participles.
 */
return [
    'status.awaiting_payment' => 'بانتظار الدفع',
    'status.cancelled' => 'ملغاة',
    'status.expired' => 'منتهية الصلاحية',
    'status.failed' => 'فشل الدفع',
    'status.paid' => 'مدفوعة',
    'status.partially_refunded' => 'مستردة جزئياً',
    'status.pending' => 'قيد الانتظار',
    'status.refunded' => 'مستردة',

    'reference' => 'المرجع',
    'placed_on' => 'تاريخ التسجيل',
    'address' => 'العنوان',
    'amount' => 'المبلغ',
    'no_orders' => 'لا توجد تسجيلات بعد.',
    'pay_now' => 'الدفع الآن',
    'download_invoice' => 'تحميل الفاتورة',

    // --- التسجيل ----------------------------------------------------------
    'title' => 'التسجيل في المؤتمر',
    'ticket' => 'الفئة',
    'quantity' => 'العدد',
    'member_places' => 'المقاعد للأعضاء',
    'member_places_exceed' => 'لا يمكن أن يتجاوز عدد مقاعد الأعضاء العدد الإجمالي للمقاعد.',
    'participants' => 'المشاركون',
    'participant_name' => 'الاسم الكامل',
    'participant_number' => 'المشارك :number',
    'participant_help' => 'أدخل بطاقة لكل مقعد محجوز. يُطبَّق سعر العضو المستند إلى وضعيتك كعضو نشط.',
    'seats_help' => 'أضف مقعدًا أو أزله قبل ملء البطاقات. يُعاد احتساب المجموع عند كل تغيير.',
    'seats_for' => 'المقاعد الخاصة بـ :ticket',
    'add_participant' => 'إضافة مشارك',
    'remove_participant' => 'إزالة آخر مشارك',
    'participant_added' => 'تمت إضافة المشارك.',
    'participant_removed' => 'تمت إزالة المشارك.',
    'participant_count' => ':count مشاركًا',
    'participant_count_one' => 'مشارك واحد',
    'min_one_place' => 'يجب أن يبقى مقعد واحد على الأقل لكل فئة.',
    'max_places_reached' => 'لا يمكنك حجز أكثر من :max مقعدًا لنفس الفئة.',
    'cart_line_missing' => 'لم تعد هذه الفئة موجودة في سلتك.',
    'items' => 'تفاصيل الطلب',
    'total' => 'المجموع',
    'member_saving_applied' => 'طُبِّق سعر العضو على :count مقعد.',
    'participant_count_mismatch' => 'حجزت :expected مقعدًا، وتم إرسال :count نماذج للمشاركين.',
    'participant_mismatch' => 'عدد المشاركين (:received) لا يطابق عدد المقاعد المحجوزة (:expected).',
    'billing_details' => 'بيانات الفوترة',
    'place_order' => 'تأكيد والدفع',
    'email_receipt' => 'سيتم إرسال رسالة تأكيد إلى بريدك الإلكتروني.',
    'summary' => 'ملخص الطلب',
    'status' => 'الحالة',

    // --- Invoice ---------------------------------------------------------
    // An order paid for before its seats were named: the attendee list is
    // collected by the organiser afterwards, so an empty one is expected
    // rather than a fault, and the page has to say so instead of showing
    // nothing under a heading.
    'participants_pending' => 'سيتم تسجيل المشاركين قبل عقد المؤتمر. لإضافتهم، يرجى الاتصال بنا.',

    // Closing band on the invoice: the purchase is finished, and what the
    // delegate wants next is the thing the ticket is for.
    'cta_lede' => 'تم حفظ تسجيلك. اطّلع على برنامج المؤتمر والمعلومات العملية حول فضاء انعقاده.',

    // Printed from the browser. The PDF download is the document to keep; this
    // is for the delegate who needs a copy today and would rather not open a
    // reader.
    'print' => 'طباعة',
    'confirm_and_pay' => 'تأكيد الطلب',
    'print_hint' => 'اطبع هذه الصفحة إلى ملف PDF من نافذة الطباعة في متصفحك.',

    // Shown before payment. The order can be opened, printed and paid at this
    // point; what does not exist yet is the *facture*, because issuing a
    // numbered invoice for money that has not arrived would put a document in
    // circulation the bank will not honour. The delegate can still print this
    // page as a receipt, so the wording promises exactly that.
    'unpaid_notice' => 'لم يتم تسديد هذا الطلب بعد. يمكنك طباعته كإيصال ودفعه الآن؛ وستصبح الفاتورة المرقّمة متاحة بعد إتمام الدفع.',

    // --- The invoice document ----------------------------------------------
    // The heading, totals block and reassurance strip of the invoice page.
    // `invoice_heading` takes the reference as a :reference placeholder rather
    // than composing it in the view, so each language can place it in its own
    // word order — French and Arabic put it differently.
    'invoice_heading' => 'الفاتورة رقم :reference',
    'paid_to' => 'الدفع إلى',
    'payment_method' => 'طريقة الدفع',
    'payment_method_cmi' => 'البطاقات المغربية والدولية (CMI)',
    'subtotal' => 'المجموع الفرعي',
    'member_saving' => 'خصم Adriاء',
    'email' => 'البريد الإلكتروني',
    'phone' => 'الهاتف',
    'contact_us' => 'اتصل بـ ARABCIA',
    'vat' => 'الضريبة على القيمة المضافة',
    'total_incl' => 'المجموع شامل الضريبة',
    'offer' => 'اسم العرض',
    'participant_name' => 'اسم المشاركين',
    'date' => 'التاريخ',
    'price_incl' => 'السعر شامل الضريبة',
    'download_invoice_short' => 'طباعة / تحميل',
    'help_title' => 'هل تحتاج إلى مساعدة؟',
    'help_text' => 'لأي سؤال حول طلبك أو عملية الدفع، تواصل مع فريق ARABCIA.',
    'assurances' => [
        'secure' => 'دفع آمن',
        'secure_text' => 'معاملاتك محمية ومؤمّنة من طرف CMI.',
        'cards' => 'البطاقات المغربية والدولية',
        'cards_text' => 'الدفع عبر البطاقات البنكية المغربية والدولية (CMI).',
        'instant' => 'فاتورة فورية',
        'instant_text' => 'تصل الفاتورة مباشرة بعد إتمام الدفع.',
        'support' => 'المساعدة',
        'support_text' => 'فريقنا رهن الإشارة متى احتجت إلى أي شيء.',
    ],
    'sign_in_to_checkout' => 'يرجى تسجيل الدخول لإتمام التسجيل.',
    'enroll_to_checkout' => 'يرجى إعداد تطبيق المصادقة قبل إتمام التسجيل.',
    'not_payable' => 'لم يعد من الممكن دفع هذا الطلب.',
    'capacity_reached' => 'اكتمل عدد المقاعد (:capacity مقعدًا، منها :taken محجوزة). يرجى الاتصال بنا للانضمام إلى قائمة الانتظار.',

    // --- السلة ------------------------------------------------------------
    'orders' => 'طلباتي',
    'cart_empty' => 'سلتك فارغة.',
    'cart_updated' => 'تم تحديث سلتك.',
    'cart_emptied' => 'تم إفراغ سلتك.',

    'cart' => [
        'add' => 'أضف إلى السلة',
        'title' => 'سلتي',
        'subtitle' => 'راجع المقاعد التي ترغب في حجزها قبل المتابعة.',
        'estimate' => 'المجموع التقديري',
        'estimate_note' => 'هذا المبلغ تقديري. يُحتسب المجموع النهائي، الذي يراعي صفتك كعضو، في الخطوة التالية.',
        'summary_note' => 'تُدخَل بيانات المشاركين في الخطوة التالية.',
        'continue' => 'متابعة التسجيل',
        'clear' => 'إفراغ السلة',
        'checkout' => 'إتمام الطلب',
        'sign_in_note' => 'سيُطلب منك تسجيل الدخول قبل الدفع. وسيتم الاحتفاظ بسلتك.',

        // منفذ إلى قائمة طلبات/مدفوعات المستخدم المتصل الكاملة، يُعرض في السلة.
        'my_payments' => 'مدفوعاتي',
        'my_payments_note' => 'اطّلع على طلباتك وحالة مدفوعاتك.',

        // ملخص الطلب القابل للطباعة والتحميل.
        //
        // السلة هي تحديدًا الموضع الذي تفيد فيه هذه الوثيقة: فقبل إتمام الطلب
        // يبقى على مندوب الشركة أن يحصل على موافقة مصلحة المشتريات. لذلك العنوان
        // «ملخص» لا «فاتورة»؛ انظر order.invoice.proforma_* للسبب نفسه في جهة
        // الطلب.

        'places' => 'مكان واحد',
        'places_plural' => ':count أماكن',

        'member_places' => 'مكان واحد للأعضاء',
        'member_places_plural' => ':count أماكن للأعضاء',

        'invoice' => 'ملخص الطلب',
        'invoice_lede' => 'حمِّل ملخص سلتك أو اطبعه لتقدّمه إلى مصلحة المشتريات أو إلى الإدارة المالية.',
        'invoice_download' => 'تحميل الملف بصيغة PDF',
        'invoice_print' => 'طباعة الملخص',
        'invoice_hint' => 'وثيقة مؤقتة بلا رقم: ليست فاتورة.',
    ],

    // --- الدفع ------------------------------------------------------------
    'payment' => [
        'confirmed' => 'تم تأكيد الدفع. تتوفر الفاتورة الخاصة بك ضمن طلباتك.',
        'failed' => 'لم تتم عملية الدفع.',
        'generic_failure' => 'تم رفض عملية الدفع.',
        'pending' => 'بانتظار تأكيد البنك للدفع.',
        'pending_note' => 'إذا كنت قد دفعت، فسيتم تأكيد تسجيلك بعد لحظات. تتحدث هذه الصفحة تلقائيًا.',
        'cancelled' => 'تم إلغاء الدفع.',
        'cancelled_note' => 'لم يتم خصم أي مبلغ من طلبك. يمكنك استئناف الدفع في أي وقت.',
        'retry' => 'استئناف الدفع',
        'secure' => 'دفع آمن عبر CMI.',
        'last_attempt' => 'آخر محاولة للدفع',
        'test_title' => 'صفحة اختبار بوابة الدفع',
        'test_banner' => 'بيئة اختبار — لن يتم تنفيذ أي دفع حقيقي.',
        'test_intro' => 'تتيح هذه الصفحة تجربة مسار الدفع الكامل دون بطاقة بنكية.',
        'test_approve' => 'محاكاة دفع مقبول',
        'test_decline' => 'محاكاة دفع مرفوض',
        'test_note' => 'يرسل الزران حمولة موقّعة إلى نقطة الاستدعاء الحقيقية، حتى يتم اختبار مسار الإنتاج فعليًا.',
    ],

    'invoice' => [
        'not_available' => 'هذا الطلب مغلق: لا يمكن إصدار أي فاتورة له.',
        'title' => 'فاتورة رقم :number',
        'billed_to' => 'الفاتورة إلى',
        'details' => 'المعلومات',
        'issued_on' => 'تاريخ الإصدار',
        'method' => 'وسيلة الدفع',
        'cmi' => 'البطاقات المغربية والدولية (CMI)',
        'subtotal' => 'المجموع الفرعي',
        'discount' => 'الخصم',
        'tax' => 'الضريبة',
        'rate' => 'الفئة',
        'footer' => 'مستند صادر عن :organiser. يجب تقديم أي طلب تصحيح إلى الجهة المنظمة.',

        // --- الفاتورة المؤقتة -------------------------------------------------
        // نصّ الوثيقة حين يكون الطلب غير مسدَّد.
        //
        // مندوب الشركة يحتاج إلى ورقة *قبل* الدفع، فمصلحة المشتريات لا تُطلق
        // الموارد مقابل لقطة شاشة لسلة. لكن هذه الورقة لا يمكن أن تكون فاتورة
        // مرقّمة، لأن رقم الفاتورة سجلّ محاسبي، وإصدار رقم لمبلغ لم يُحصَّل
        // يضع في التداول وثيقة لا يقبلها البنك. لذلك عنوان «مؤقتة»، وغياب
        // الرقم، وتحذير صريح بأنها ليست إثباتًا للدفع.

        'proforma_title' => 'فاتورة مؤقتة',
        'proforma_banner_title' => 'وثيقة مؤقتة — الطلب غير مسدَّد',
        'proforma_banner_text' => 'هذه ليست فاتورة. فهي تلخّص طلبك للاستئناس، ولا تصدر الفاتورة المرقّمة النهائية إلا بعد التحصيل.',
        'proforma_notes_title' => 'ملاحظة',
        'proforma_note_estimate' => 'المبالغ إرشادية. لا يُطبَّق سعر العضوية إلا على المشاركين المطابَقين بسجل عضوية نشط لحظة تقديم الطلب.',
        'proforma_note_issue' => 'لم يُسنَد رقم فاتورة لهذه الوثيقة، وهي لا تحلّ محلّ الفاتورة.',
        'billed_to_pending' => 'يُستكمل عند إتمام الطلب.',
    ],

    // --- البريد الإلكتروني -------------------------------------------------
    'mail' => [
        'paid_subject' => 'ARABCIA 2026 — تم تأكيد التسجيل (:reference)',
        'failed_subject' => 'ARABCIA 2026 — لم تتم عملية الدفع (:reference)',
        'paid_intro' => "مرحبًا،\n\nتم تأكيد تسجيلك في مؤتمر ARABCIA 2026. وقد سجّلنا دفعك بنجاح.",
        'failed_intro' => "مرحبًا،\n\nلم تتم عملية الدفع الخاصة بتسجيلك في مؤتمر ARABCIA 2026.",
        'invoice_attached' => 'الفاتورة مرفقة بهذه الرسالة.',
        'invoice_online' => 'يمكنك تحميل الفاتورة من مساحة «طلباتي».',
        'failed_action' => 'يمكنك استئناف الدفع في أي وقت من مساحة «طلباتي».',
        'footer' => 'مع التحيات، لجنة ARABCIA 2026.',
    ],

    // --- الإلغاء ----------------------------------------------------------
    'cancel' => [
        'done' => 'تم إلغاء طلبك.',
        'already' => 'هذا الطلب مغلق بالفعل.',
        'paid' => 'هذا الطلب مدفوع ولا يمكن إلغاؤه. يرجى الاتصال بالمنظمين لاسترداد المبلغ.',
        'action' => 'إلغاء الطلب',
        'confirm' => 'هل تريد تأكيد إلغاء هذا الطلب؟',
    ],
];
