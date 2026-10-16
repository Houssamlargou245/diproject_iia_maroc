{{--
    Conditions générales d'inscription et de participation.

    The document also published as /conditions.pdf, rendered as structured HTML
    so it reads in place and can be searched, with the PDF kept as the
    authoritative download. Mirrors the sister partial for the mentions légales.

    The body is French, the language of the published document; the frame around
    it (kicker, title, download label, note) is localised.

    NOTE: the source PDF has no "Article 15" heading — its body text sits
    between Article 14 and Article 16 with the heading omitted. It is restored
    here as "Réclamations", read from that text; correct it if the official
    wording differs.
--}}

<div class="d-doc">
    <header class="d-modal__banner" style="background-image: url('{{ asset('assets/images/images.png') }}');">
        <p class="d-modal__eyebrow">
            <i class="fas fa-file-signature" aria-hidden="true"></i>
            @lang('terms.kicker')
        </p>

        <h2 class="d-modal__title">@lang('terms.title')</h2>

        <p class="d-modal__lede">@lang('terms.lede')</p>

        <p class="d-modal__meta">
            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
            @lang('terms.updated')
        </p>
    </header>

    <div class="d-modal__toolbar">
        <span class="d-modal__hint">@lang('terms.hint')</span>

        <a class="d-modal__btn d-modal__btn--primary"
          style="color: #fff !important;"
           href="{{ asset('conditions.pdf') }}"
           download="conditions.pdf"
           target="_blank"
           rel="noopener">
            <i class="fas fa-file-pdf" aria-hidden="true"></i>
            @lang('terms.download')
        </a>
    </div>

    <div class="d-doc__body">
        {{-- 1 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">1</span> Objet</h3>

            <p>
                Les présentes Conditions générales de vente et de participation (CGV) définissent
                les modalités d’inscription, de paiement et de participation à la Conférence
                internationale ARABCIA 2026, organisée les 16 et 17 décembre 2026 à Rabat, au
                Maroc.
            </p>
            <p>
                La conférence est organisée par la Confédération Arabe des Instituts d’Audit
                Interne (ARABCIA), avec l’Institut des Auditeurs Internes du Maroc
                (IIA Maroc – AMACI) en qualité d’institut hôte.
            </p>
            <p>
                IIA Maroc est seul responsable de la commercialisation des inscriptions, de leur
                gestion administrative, de la facturation, de l’encaissement des paiements et du
                traitement des demandes des participants.
            </p>
            <p>Toute inscription à la conférence implique l’acceptation des présentes conditions générales.</p>
        </section>

        {{-- 2 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">2</span> Identification du responsable des inscriptions</h3>

            <div class="d-doc__entity">
                <h4>IIA Maroc – Institut des Auditeurs Internes du Maroc (AMACI)</h4>
                <p>
                    Adresse : Boulevard Sidi Mohamed Ben Abdellah, Résidence Bellevue,
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
                IIA Maroc constitue l’interlocuteur contractuel des participants pour toute
                question relative aux inscriptions, paiements, factures, annulations et
                remboursements.
            </p>
        </section>

        {{-- 3 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">3</span> Modalités d’inscription</h3>

            <p>Les inscriptions s’effectuent en ligne, à travers la plateforme officielle de la conférence.</p>
            <p>Deux possibilités sont proposées :</p>
            <ul>
                <li>
                    <strong>Participants disposant déjà d’un compte IIA Maroc :</strong>
                    connexion à l’aide de leurs identifiants habituels.
                </li>
                <li>
                    <strong>Nouveaux participants :</strong>
                    création d’un compte personnel permettant de renseigner les informations
                    nécessaires à l’inscription.
                </li>
            </ul>
            <p>Le participant s’engage à communiquer des informations exactes et complètes.</p>
            <p>
                Toute inscription est soumise à la disponibilité des places et à la validation du
                paiement selon les modalités proposées.
            </p>
            <p>
                Une confirmation d’inscription est adressée par courrier électronique après
                validation définitive.
            </p>
        </section>

        {{-- 4 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">4</span> Tarifs d’inscription</h3>

            <p>Les tarifs applicables à la Conférence internationale ARABCIA 2026 sont les suivants :</p>

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
                Le tarif adhérent est réservé aux personnes pouvant justifier d’une adhésion en
                cours de validité, selon les critères retenus par IIA Maroc.
            </p>
            <p>
                Les tarifs sont exprimés en dirhams marocains (MAD) ou en dollars américains
                (USD), selon la devise de facturation et de paiement proposée.
            </p>
            <p>
                Le traitement fiscal des tarifs et les éventuels frais bancaires sont précisés
                avant la validation du paiement.
            </p>
            <p>
                IIA Maroc se réserve la possibilité de proposer des conditions tarifaires
                particulières, sous réserve de les communiquer clairement aux bénéficiaires
                concernés.
            </p>
        </section>

        {{-- 5 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">5</span> Prestations incluses</h3>

            <p>L’inscription à la conférence comprend :</p>
            <ul>
                <li>L’accès aux deux journées de la conférence, les 16 et 17 décembre 2026 ;</li>
                <li>
                    L’accès aux conférences, sessions plénières et interventions, dans la limite
                    des capacités d’accueil et des conditions d’accès propres à chaque session ;
                </li>
                <li>
                    La participation aux ateliers et Innovation Labs, selon les modalités
                    d’organisation et les places disponibles ;
                </li>
                <li>
                    La délivrance d’un certificat de participation, sous réserve de la présence
                    effective du participant.
                </li>
            </ul>
            <p>
                Les frais de transport, d’hébergement et les dépenses personnelles ne sont pas
                inclus dans les tarifs d’inscription.
            </p>
            <p>
                Les prestations de restauration éventuellement comprises dans l’inscription sont
                précisées dans le programme officiel ou lors de l’inscription.
            </p>
        </section>

        {{-- 6 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">6</span> Paiement et confirmation</h3>

            <p>
                Le paiement des frais d’inscription est effectué selon les moyens proposés par
                IIA Maroc sur la plateforme ou communiqués officiellement au participant.
            </p>
            <p>
                L’inscription est considérée comme définitive après réception et validation du
                paiement intégral, sauf accord écrit particulier d’IIA Maroc.
            </p>
            <p>
                Une confirmation est alors adressée au participant à l’adresse électronique
                communiquée lors de son inscription.
            </p>
            <p>
                Une facture ou un justificatif de paiement est établi par IIA Maroc conformément
                aux dispositions applicables.
            </p>
            <p>
                En cas de paiement non abouti, IIA Maroc peut inviter le participant à
                régulariser sa situation avant de confirmer son inscription.
            </p>
        </section>

        {{-- 7 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">7</span> Annulation par le participant</h3>

            <p>
                Toute demande d’annulation doit être adressée par écrit à IIA Maroc, à l’adresse
                suivante : <a href="mailto:info@iiamaroc.org">info@iiamaroc.org</a>.
            </p>
            <p>
                Les conditions d’annulation et de remboursement sont les suivantes, sous réserve
                des droits impératifs prévus par la législation applicable :
            </p>
            <ul>
                <li>
                    <strong>Jusqu’au 30 novembre 2026 inclus :</strong>
                    remboursement intégral des frais d’inscription.
                </li>
                <li>
                    <strong>Du 1<sup>er</sup> au 10 décembre 2026 inclus :</strong>
                    remboursement de 50 % des frais d’inscription.
                </li>
                <li>
                    <strong>À compter du 11 décembre 2026 :</strong>
                    aucun remboursement contractuel n’est prévu, sauf disposition légale
                    impérative contraire ou décision exceptionnelle d’IIA Maroc.
                </li>
            </ul>
            <p>
                Toute demande d’annulation doit préciser l’identité du participant et les
                références de son inscription.
            </p>
            <p>
                Les remboursements acceptés sont effectués dans un délai maximal de 30 jours
                suivant leur validation, sans préjudice de tout délai légal plus favorable.
            </p>
            <p>Les éventuels droits légaux de rétractation applicables demeurent réservés.</p>
        </section>

        {{-- 8 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">8</span> Remplacement d’un participant</h3>

            <p>
                Lorsqu’un participant inscrit ne peut assister à la conférence, il peut demander
                son remplacement par une autre personne.
            </p>
            <p>La demande doit être adressée à IIA Maroc avant le 11 décembre 2026.</p>
            <p>
                Le remplacement est soumis à la communication des informations nécessaires
                concernant le nouveau participant et à la vérification de son éligibilité à la
                catégorie tarifaire initialement retenue.
            </p>
            <p>Une éventuelle différence de tarif pourra être demandée.</p>
            <p>Le remplacement devient effectif après confirmation écrite d’IIA Maroc.</p>
        </section>

        {{-- 9 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">9</span> Modification ou annulation de la conférence</h3>

            <p>
                IIA Maroc se réserve la possibilité, en coordination avec ARABCIA, d’apporter des
                modifications au programme, aux horaires, aux intervenants ou à l’organisation
                pratique de la conférence.
            </p>
            <p>
                Ces modifications ne donnent pas automatiquement lieu à remboursement
                lorsqu’elles n’affectent pas substantiellement les prestations contractuellement
                prévues, sous réserve des droits applicables aux participants.
            </p>
            <p>
                En cas d’annulation définitive de la conférence, les frais d’inscription
                effectivement encaissés seront remboursés intégralement aux participants
                concernés.
            </p>
            <p>
                En cas de report de l’événement, IIA Maroc communiquera les nouvelles dates et
                proposera aux participants le maintien de leur inscription ou le remboursement
                des frais versés.
            </p>
            <p>
                Les dépenses personnelles engagées par les participants, notamment pour le
                transport ou l’hébergement, restent à leur charge, sauf disposition légale
                contraire ou engagement exprès d’IIA Maroc.
            </p>
        </section>

        {{-- 10 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">10</span> Conditions de participation</h3>

            <p>
                Les participants s’engagent à respecter les règles d’organisation, de sécurité et
                de bonne conduite applicables pendant la conférence.
            </p>
            <p>
                Les badges d’accès sont personnels et ne peuvent être cédés sans accord préalable
                d’IIA Maroc.
            </p>
            <p>
                IIA Maroc peut refuser l’accès ou demander le départ d’un participant dont le
                comportement compromet la sécurité ou le bon déroulement de l’événement, dans le
                respect des dispositions légales applicables.
            </p>
        </section>

        {{-- 11 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">11</span> Propriété intellectuelle</h3>

            <p>
                Les présentations, documents, interventions, supports pédagogiques et autres
                contenus diffusés dans le cadre de la conférence demeurent la propriété
                intellectuelle de leurs auteurs ou titulaires respectifs.
            </p>
            <p>
                Toute reproduction, diffusion, publication ou exploitation commerciale de ces
                contenus nécessite l’autorisation préalable des titulaires des droits concernés,
                sauf exceptions prévues par la loi.
            </p>
            <p>
                L’enregistrement audiovisuel des interventions par les participants est soumis
                aux consignes communiquées par les organisateurs.
            </p>
        </section>

        {{-- 12 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">12</span> Photographies et captations audiovisuelles</h3>

            <p>
                Des photographies et vidéos peuvent être réalisées pendant la conférence afin de
                documenter l’événement et d’en assurer la communication institutionnelle.
            </p>
            <p>
                Les participants sont informés des modalités de captation et d’utilisation de
                leur image, conformément aux dispositions légales applicables.
            </p>
            <p>
                Lorsque le consentement est requis, les utilisations concernées sont subordonnées
                à son obtention.
            </p>
            <p>
                Les participants peuvent contacter IIA Maroc pour toute question relative à
                l’utilisation de leur image.
            </p>
        </section>

        {{-- 13 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">13</span> Protection des données personnelles</h3>

            <p>
                IIA Maroc est le seul responsable du traitement des données personnelles
                collectées dans le cadre de la gestion des comptes, des inscriptions, des
                paiements et de la participation à la conférence.
            </p>
            <p>
                Ces données sont utilisées pour assurer la gestion administrative de l’événement,
                communiquer les informations nécessaires aux participants, établir les documents
                de facturation et délivrer les certificats de participation.
            </p>
            <p>
                Le traitement est effectué conformément à la loi marocaine n° 09-08 relative à la
                protection des données personnelles.
            </p>
            <p>
                Les participants disposent des droits prévus par la législation applicable,
                notamment des droits d’accès, de rectification et d’opposition, qu’ils peuvent
                exercer auprès d’IIA Maroc à l’adresse
                <a href="mailto:info@iiamaroc.org">info@iiamaroc.org</a>.
            </p>
            <p>
                Les modalités détaillées du traitement figurent dans la Politique de
                confidentialité du site.
            </p>
        </section>

        {{-- 14 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">14</span> Responsabilité</h3>

            <p>
                IIA Maroc s’engage à mettre en œuvre les moyens nécessaires à la bonne gestion des
                inscriptions et à la coordination des prestations relevant de sa responsabilité.
            </p>
            <p>
                Sa responsabilité est appréciée conformément à la législation applicable et aux
                obligations contractuelles souscrites.
            </p>
            <p>
                IIA Maroc ne saurait être tenu responsable des préjudices résultant exclusivement
                d’un événement de force majeure, dans les conditions reconnues par le droit
                applicable.
            </p>
            <p>
                Aucune disposition des présentes CGV ne peut avoir pour effet d’exclure ou de
                limiter une responsabilité qui ne pourrait légalement l’être.
            </p>
        </section>

        {{-- 15 — heading restored; absent from the source PDF --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">15</span> Réclamations</h3>

            <p>
                IIA Maroc s’engage à examiner les réclamations reçues et à apporter une réponse
                dans un délai raisonnable.
            </p>
            <p>Une résolution amiable sera privilégiée avant toute procédure contentieuse.</p>
        </section>

        {{-- 16 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">16</span> Droit applicable et règlement des différends</h3>

            <p>
                Les présentes Conditions générales de vente sont soumises au droit marocain, sous
                réserve des règles impératives éventuellement applicables.
            </p>
            <p>
                En cas de différend, les parties s’efforceront de rechercher une solution
                amiable.
            </p>
            <p>
                À défaut d’accord, le litige sera soumis aux juridictions compétentes conformément
                aux règles légales applicables.
            </p>
        </section>

        {{-- 17 --}}
        <section class="d-doc__section">
            <h3><span class="d-doc__num">17</span> Acceptation des conditions générales</h3>

            <p>
                La validation d’une inscription implique que le participant reconnaît avoir pris
                connaissance des présentes Conditions générales de vente et de participation et
                les avoir acceptées.
            </p>
            <p>
                Cette acceptation est recueillie au moyen d’une case à cocher non précochée avant
                la confirmation de la commande.
            </p>
            <p>
                Les conditions applicables sont celles portées à la connaissance du participant et
                acceptées au moment de son inscription.
            </p>
        </section>

        <div class="d-doc__entity">
            <p><strong>IIA Maroc</strong> – Institut des Auditeurs Internes du Maroc</p>
            <p>Institut hôte de la Conférence internationale ARABCIA 2026</p>
            <p>Dernière mise à jour : octobre 2026.</p>
        </div>

        <p class="d-doc__note">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            <span>@lang('terms.note')</span>
        </p>
    </div>
</div>
