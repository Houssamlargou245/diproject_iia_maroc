{{--
    Mentions légales.

    The document also published as /MentionsLegales.pdf, rendered as structured
    HTML so it can be read in place, scrolled, zoomed and found with the
    browser's own search — while the PDF stays the authoritative download.

    The body text is French on purpose: that is the language of the published
    document, and the modal says so. Everything around it (kicker, title,
    download label, the note) is localised, so the frame speaks the visitor's
    language even when the legal text does not.
--}}

<div class="d-doc">
    <header class="d-modal__banner">
        <p class="d-modal__eyebrow">
            <i class="fas fa-balance-scale" aria-hidden="true"></i>
            @lang('legal.kicker')
        </p>

        <h2 class="d-modal__title">@lang('legal.title')</h2>

        <p class="d-modal__lede">@lang('legal.lede')</p>

        <p class="d-modal__meta">
            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
            @lang('legal.updated')
        </p>
    </header>

    <div class="d-modal__toolbar">
        <span class="d-modal__hint">@lang('legal.hint')</span>

        <a class="d-modal__btn d-modal__btn--primary"
           style="color: #fff !important;"
           href="{{ asset('MentionsLegales.pdf') }}"
           download="MentionsLegales.pdf"
           target="_blank"
           rel="noopener">
            <i class="fas fa-file-pdf" aria-hidden="true"></i>
            @lang('legal.download')
        </a>
    </div>

    <div class="d-doc__body">
        {{-- 1 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">1</span> Présentation du site</h3>

            <p>
                Le présent site internet est dédié à la Conférence internationale ARABCIA 2026,
                qui se tiendra les 16 et 17 décembre 2026 au Four Seasons Hotel Rabat, au Maroc,
                sous le thème :
            </p>

            <p>
                <strong>« L’Audit Interne, partenaire de confiance dans la transformation et la
                résilience des organisations ».</strong>
            </p>

            <p>
                Cet événement est organisé par la Confédération Arabe des Instituts d’Audit
                Interne (ARABCIA), avec l’Institut des Auditeurs Internes du Maroc
                (IIA Maroc – AMACI) en qualité d’institut hôte.
            </p>

            <p>
                Le site a pour vocation de présenter la conférence, son programme, ses
                intervenants, ses partenaires et les modalités de participation. Il permet
                également aux visiteurs de s’inscrire à l’événement et de soumettre des demandes
                d’information ou de sponsoring.
            </p>
        </section>

        {{-- 2 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">2</span> Organisateur de la conférence</h3>

            <div class="d-doc__entity">
                <h4>ARABCIA – Arab Confederation of Internal Auditors</h4>
                <p>
                    Confédération professionnelle regroupant les instituts et associations
                    d’audit interne des pays arabes.
                </p>
                <p>Siège : 2905 Al Jamiah Street, Al Malaz, Riyad 12641, Royaume d’Arabie saoudite.</p>

                <div class="d-doc__contact">
                    <a href="mailto:info@arabciia.org">
                        <i class="fas fa-envelope" aria-hidden="true"></i> info@arabciia.org
                    </a>
                    <a href="https://www.arabciia.org" target="_blank" rel="noopener">
                        <i class="fas fa-globe" aria-hidden="true"></i> www.arabciia.org
                    </a>
                </div>
            </div>

            <p>
                ARABCIA assure l’organisation générale de la conférence, en collaboration avec
                IIA Maroc, institut hôte de l’événement.
            </p>
        </section>

        {{-- 3 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">3</span> Institut hôte</h3>

            <div class="d-doc__entity">
                <h4>IIA Maroc – Institut des Auditeurs Internes du Maroc (AMACI)</h4>
                <p>
                    Association professionnelle à but non lucratif régie par la législation
                    marocaine relative au droit d’association.
                </p>
                <p>
                    Siège social : Boulevard Sidi Mohamed Ben Abdellah, Résidence Bellevue,
                    Immeuble B, 2<sup>e</sup> étage, Bureau 1, Casablanca, Maroc.
                </p>

                <div class="d-doc__contact">
                    <a href="tel:+212522224422" dir="ltr">
                        <i class="fas fa-phone" aria-hidden="true"></i> +212 5 22 22 44 22
                    </a>
                    <a href="mailto:info@iiamaroc.org">
                        <i class="fas fa-envelope" aria-hidden="true"></i> info@iiamaroc.org
                    </a>
                    <a href="https://www.iiamaroc.org" target="_blank" rel="noopener">
                        <i class="fas fa-globe" aria-hidden="true"></i> www.iiamaroc.org
                    </a>
                </div>
            </div>

            <p>
                IIA Maroc contribue à l’organisation et à la coordination locale de la
                conférence, notamment à l’accueil des participants et à la mise en œuvre des
                activités prévues dans le cadre de l’événement.
            </p>
        </section>

        {{-- 4 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">4</span> Édition et gestion du site</h3>

            <p>
                Le présent site est publié dans le cadre de l’organisation de la Conférence
                internationale ARABCIA 2026.
            </p>
            <p>
                Les contenus et informations publiés sont placés sous la responsabilité des
                institutions concernées, conformément à leurs attributions respectives.
            </p>
            <p>
                <strong>Responsable de publication :</strong>
                [Nom et fonction à confirmer auprès des organisateurs].
            </p>
            <p>
                <strong>Contact relatif au site et à la conférence :</strong>
                <a href="mailto:info@iiamaroc.org">info@iiamaroc.org</a>.
            </p>
        </section>

        {{-- 5 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">5</span> Conception et développement</h3>

            <p>Le site a été conçu et développé par : <strong>Di com – Direct Invest</strong>.</p>
            <p>Agence de communication et de développement digital basée à Casablanca, Maroc.</p>
            <p>
                Site internet :
                <a href="https://www.dicom.ma" target="_blank" rel="noopener">www.dicom.ma</a>.
            </p>
        </section>

        {{-- 6 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">6</span> Hébergement du site</h3>

            <p>Le présent site internet est hébergé par :</p>

            <div class="d-doc__entity">
                <h4>HEBERJAHIZ – Arcanes Technologies</h4>
                <p>
                    Adresse : Casablanca Technopark, RDC, Bureau 143bis, Route de Nouaceur,
                    Casablanca 20153, Maroc.
                </p>

                <div class="d-doc__contact">
                    <a href="tel:+212522491944" dir="ltr">
                        <i class="fas fa-phone" aria-hidden="true"></i> +212 5 22 49 19 44
                    </a>
                    <a href="mailto:commercial@heberjahiz.com">
                        <i class="fas fa-envelope" aria-hidden="true"></i> commercial@heberjahiz.com
                    </a>
                    <a href="https://www.heberjahiz.com" target="_blank" rel="noopener">
                        <i class="fas fa-globe" aria-hidden="true"></i> www.heberjahiz.com
                    </a>
                    <span>
                        <i class="fas fa-file-contract" aria-hidden="true"></i>
                        Registre du commerce : Casablanca, n° 124307.
                    </span>
                </div>
            </div>
        </section>

        {{-- 7 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">7</span> Propriété intellectuelle</h3>

            <p>
                L’ensemble des contenus présents sur ce site, notamment les textes,
                photographies, illustrations, éléments graphiques, logos, documents et supports
                de présentation, est protégé par les dispositions légales applicables en matière
                de propriété intellectuelle.
            </p>
            <p>
                Ces éléments appartiennent à ARABCIA, à IIA Maroc ou à leurs titulaires
                respectifs.
            </p>
            <p>
                Toute reproduction, représentation, modification, diffusion ou exploitation,
                totale ou partielle, sans autorisation préalable des titulaires des droits
                concernés est interdite, sauf exceptions prévues par la législation applicable.
            </p>
            <p>
                Les marques et logos des partenaires, sponsors et intervenants demeurent la
                propriété de leurs titulaires respectifs.
            </p>
        </section>

        {{-- 8 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">8</span> Protection des données personnelles</h3>

            <p>
                Dans le cadre de l’utilisation du site, des données personnelles peuvent être
                collectées, notamment à travers les formulaires d’inscription, de création de
                compte, de contact et de demande de sponsoring.
            </p>
            <p>Ces données sont utilisées pour les finalités suivantes :</p>
            <ul>
                <li>La gestion des inscriptions et de la participation à la conférence ;</li>
                <li>Le traitement des demandes d’information et de sponsoring ;</li>
                <li>La gestion administrative et financière des inscriptions ;</li>
                <li>La communication d’informations pratiques relatives à l’événement ;</li>
                <li>La délivrance des attestations ou certificats de participation.</li>
            </ul>
            <p>
                Le traitement des données personnelles est effectué dans le respect de la
                législation applicable, notamment de la loi marocaine n° 09-08 relative à la
                protection des personnes physiques à l’égard du traitement des données à
                caractère personnel, lorsque celle-ci s’applique.
            </p>
            <p>
                Les données collectées sont accessibles uniquement aux personnes et prestataires
                habilités, dans la limite de leurs missions et des finalités annoncées.
            </p>
            <p>
                Les modalités détaillées de collecte, d’utilisation, de conservation, de
                sécurisation et d’exercice des droits des personnes concernées sont précisées
                dans la Politique de confidentialité du site.
            </p>
        </section>

        {{-- 9 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">9</span> Inscriptions et paiements</h3>

            <p>
                Les inscriptions à la Conférence internationale ARABCIA 2026 sont effectuées à
                travers la plateforme prévue à cet effet.
            </p>
            <p>Les tarifs d’inscription sont les suivants :</p>

            <div class="d-doc__table-wrap">
                <table class="d-doc__table">
                    <thead>
                        <tr>
                            <th>Catégorie</th>
                            <th>Tarif en MAD</th>
                            <th>Tarif en USD</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Adhérents</td>
                            <td>7 500 MAD</td>
                            <td>750 USD</td>
                        </tr>
                        <tr>
                            <td>Non-adhérents</td>
                            <td>8 500 MAD</td>
                            <td>850 USD</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p>
                L’inscription donne accès aux deux journées de la conférence, aux interventions
                programmées dans les conditions d’accès applicables et à un certificat de
                participation.
            </p>
            <p>
                Les modalités de paiement, de confirmation d’inscription et les éventuelles
                conditions d’annulation ou de remboursement sont précisées au cours du processus
                d’inscription ou dans les conditions générales applicables.
            </p>
        </section>

        {{-- 10 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">10</span> Responsabilité</h3>

            <p>
                Les organisateurs veillent à fournir sur ce site des informations fiables,
                exactes et régulièrement actualisées.
            </p>
            <p>
                Toutefois, des modifications peuvent intervenir concernant le programme, les
                intervenants, les horaires ou les modalités pratiques de la conférence.
            </p>
            <p>
                Les organisateurs se réservent la possibilité de procéder à ces ajustements,
                dans le respect des engagements applicables envers les participants.
            </p>
            <p>
                Ils ne sauraient être tenus responsables des interruptions temporaires du site
                résultant d’opérations de maintenance, d’incidents techniques ou d’événements
                indépendants de leur volonté, sous réserve des dispositions légales impératives.
            </p>
        </section>

        {{-- 11 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">11</span> Liens externes</h3>

            <p>
                Le site peut contenir des liens vers des sites internet tiers, notamment ceux
                des organisateurs, partenaires, sponsors et intervenants.
            </p>
            <p>Ces liens sont proposés à titre informatif.</p>
            <p>
                Les organisateurs ne sont pas responsables du contenu, de la disponibilité ni
                des pratiques de confidentialité de ces sites externes, sous réserve des
                dispositions légales applicables.
            </p>
        </section>

        {{-- 12 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">12</span> Modification des mentions légales</h3>

            <p>
                Les présentes mentions légales peuvent être actualisées afin de tenir compte des
                évolutions du site, des modalités d’organisation de la conférence ou des
                dispositions légales applicables.
            </p>
            <p>La version en vigueur est celle publiée sur le site à la date de consultation.</p>
            <p><strong>Dernière mise à jour : octobre 2026.</strong></p>
        </section>

        <p class="d-doc__note">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            <span>@lang('legal.note')</span>
        </p>
    </div>
</div>
