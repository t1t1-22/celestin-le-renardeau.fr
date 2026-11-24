<?php
if (!defined('ABSPATH')) exit;
$options=Celestin_Home::options();
$stories=Celestin_Home::featured('cel_histoire',3);
$book=Celestin_Home::latest('cel_livre');
$character=Celestin_Home::latest('cel_personnage');
$city=Celestin_Home::latest('cel_lieu');
$places=Celestin_Home::featured('cel_lieu',5);
$activity=Celestin_Home::latest('cel_coloriage');
$news=Celestin_Home::cards();
$links=[
 'Personnages'=>['url'=>Celestin_Home::url('cel_personnage'),'symbol'=>'✿'],
 'Mes petites activités'=>['url'=>Celestin_Home::url('cel_coloriage'),'symbol'=>'✎'],
 'Les livres'=>['url'=>Celestin_Home::url('cel_livre'),'symbol'=>'▤'],
 'Mini-histoires'=>['url'=>Celestin_Home::url('cel_histoire'),'symbol'=>'♫']
];
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
<style>
body.cel-page .cel-book-editorial .cel-books-layout{grid-template-columns:minmax(0, .9fr) minmax(0,1.1fr);gap:clamp(32px,6vw,85px);max-width:1000px;padding:30px 12px;align-items:center}
body.cel-page .cel-book-editorial .cel-book-description{background:transparent;border:0;padding:0;box-shadow:none}
body.cel-page .cel-book-editorial .cel-book-editorial-title{font:700 clamp(27px,3vw,38px)/1.15 'Baloo 2',sans-serif;margin:0 0 15px;color:#633619}
body.cel-page .cel-book-editorial-title a{color:inherit;text-decoration:none}
body.cel-page .cel-book-editorial .cel-book-preview{padding:24px 12px 38px 32px;perspective:1300px;display:flex;justify-content:center;position:relative;isolation:isolate}
body.cel-page .cel-book-editorial .cel-book-preview:before{content:"";position:absolute;left:50%;bottom:28px;width:84%;max-width:365px;height:22px;transform:translateX(-50%);background:radial-gradient(ellipse at center,#51371965 0%,#51371930 38%,transparent 72%);filter:blur(6px);z-index:-1;pointer-events:none}
body.cel-page .cel-book-editorial .cel-book-preview .cel-book-mockup{--book-depth:28px;position:relative;display:block;width:100%;max-width:365px;aspect-ratio:1;padding:0;border:0;border-radius:4px 9px 9px 4px;background:#f4e7d4;transform-style:preserve-3d;transform:rotateY(9deg) rotateX(0deg) rotateZ(0deg);box-shadow:none;overflow:visible;transition:none}
body.cel-page .cel-book-editorial .cel-book-mockup:before{content:"";position:absolute;inset:1px;border-radius:4px 9px 9px 4px;background:#d9791f;transform:translateZ(calc(-1 * var(--book-depth)));box-shadow:none;pointer-events:none}
body.cel-page .cel-book-editorial .cel-book-mockup:after{content:"";position:absolute;left:0;top:0;bottom:0;width:var(--book-depth);transform-origin:left center;transform:rotateY(90deg);background:linear-gradient(90deg,#b85e13 0%,#e98932 18%,#f3a24e 45%,#cf741f 100%);border-radius:3px 0 0 3px;pointer-events:none}
body.cel-page .cel-book-editorial .cel-book-mockup .cel-book-face{position:absolute;inset:0;display:block;overflow:hidden;border-radius:inherit;background:#f4e7d4;transform:translateZ(.5px);box-shadow:0 0 0 1px #88674630}
body.cel-page .cel-book-editorial .cel-book-mockup .cel-book-face:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,#33200d36 0%,#fff2 1.2%,#34220f16 2.2%,transparent 4%);pointer-events:none}
body.cel-page .cel-book-editorial .cel-book-mockup img{display:block;width:100%;height:100%;max-width:none;object-fit:contain;border-radius:inherit;box-shadow:none}
body.cel-page .cel-book-editorial .cel-book-mockup .cel-book-pages{display:none}
body.cel-page .cel-book-editorial .cel-book-preview .cel-book-mockup:hover{transform:rotateY(9deg) rotateX(0deg) rotateZ(0deg);box-shadow:none;border:0}
body.cel-page .cel-book-editorial .cel-feature-tags{margin-bottom:17px} body.cel-page .cel-book-editorial .cel-book-library-link{display:block;margin-top:12px;font-size:14px;color:#633619;text-decoration:underline}
body.cel-page .cel-book-editorial .cel-book-more summary{list-style:none;cursor:pointer;color:#633619;font-size:15px;line-height:1.65}
body.cel-page .cel-book-editorial .cel-book-more summary::-webkit-details-marker{display:none}
body.cel-page .cel-book-editorial .cel-book-more-label{display:block;color:#a75721;font-weight:700;font-size:14px;margin-top:5px}
body.cel-page .cel-book-editorial .cel-book-close,body.cel-page .cel-book-editorial .cel-book-more[open] .cel-book-open,body.cel-page .cel-book-editorial .cel-book-more[open] .cel-book-excerpt{display:none}
body.cel-page .cel-book-editorial .cel-book-more[open] .cel-book-close{display:inline}
body.cel-page .cel-book-editorial .cel-book-listen{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin:15px 0;color:#633619;font-size:14px;font-weight:700}
body.cel-page .cel-book-editorial .cel-book-listen audio{width:100%;max-width:350px;height:42px}
body.cel-page .cel-book-editorial .cel-home-book-all{margin-top:18px} body.cel-page .cel-book-editorial .cel-book-listen .cel-audio-toggle{position:relative;inset:auto;transform:none;width:48px;height:48px;flex:0 0 48px;padding:0;border:0;background:transparent;box-shadow:none} body.cel-page .cel-book-editorial .cel-book-listen .cel-audio-toggle svg{width:48px;height:48px;display:block} body.cel-page .cel-book-editorial .cel-book-listen .cel-audio-error[hidden]{display:none}
@media(max-width:760px){body.cel-page .cel-book-editorial .cel-books-layout{grid-template-columns:1fr;gap:12px;padding:15px 8px}body.cel-page .cel-book-editorial .cel-book-preview .cel-book-mockup{max-width:240px}body.cel-page .cel-book-editorial .cel-feature-tags{justify-content:flex-start}}
@media(prefers-reduced-motion:reduce){body.cel-page .cel-book-editorial .cel-book-preview .cel-book-mockup:hover{transform:rotateY(9deg) rotateX(0deg) rotateZ(0deg)}}
/* Présentation éditoriale de l’album. */
body.cel-page .cel-book-editorial .cel-book-description{display:flex;flex-direction:column;align-items:flex-start;gap:16px}
body.cel-page .cel-book-editorial .cel-book-editorial-title{margin:0;line-height:1.12;font-size:clamp(29px,3vw,40px)}
body.cel-page .cel-book-editorial .cel-feature-tags{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 18px;width:100%;margin:0;padding:14px 0;border-top:1px solid #e5d8c7;border-bottom:1px solid #e5d8c7}
body.cel-page .cel-book-editorial .cel-feature-tags>span{display:flex;flex-direction:column;align-items:flex-start;gap:3px;background:none;border:0;border-radius:0;padding:3px 0;font-size:14px;line-height:1.35}
body.cel-page .cel-book-editorial .cel-feature-tags>span>strong{font-size:10px;text-transform:uppercase;letter-spacing:.09em;color:#a75b28;font-weight:700}
body.cel-page .cel-book-editorial .cel-feature-tags>span>span{font-size:14px;background:none;border:0;padding:0;border-radius:0;font-weight:600}
body.cel-page .cel-book-editorial .cel-home-book-summary{width:100%;margin:0;font-size:15px;line-height:1.65}
body.cel-page .cel-book-editorial .cel-book-more summary{font-size:15px;line-height:1.7}
body.cel-page .cel-book-editorial .cel-book-more-label{display:inline-flex;margin:4px 0 0 5px;text-decoration:underline;text-underline-offset:3px}
body.cel-page .cel-book-editorial .cel-book-actions{display:flex;flex-wrap:wrap;align-items:center;gap:10px 14px;width:100%;margin-top:4px}
body.cel-page .cel-book-editorial .cel-book-actions .cel-home-book-all{margin:0;background:#e98932;color:#fff!important;padding:12px 20px;border-radius:25px;box-shadow:0 3px 0 #c46c23}
body.cel-page .cel-book-editorial .cel-book-actions .cel-home-book-all:hover{background:#d77929}
body.cel-page .cel-book-editorial .cel-book-actions .cel-book-library-link{display:inline-flex;align-items:center;gap:8px;margin:0;padding:10px 14px;border:1px solid #d9c5ad;border-radius:25px;text-decoration:none;font-weight:600}
body.cel-page .cel-book-editorial .cel-book-actions .cel-book-library-link:hover{background:#f2e5d5}
body.cel-page .cel-book-editorial .cel-book-listen{width:auto;display:flex;align-items:center;gap:10px;margin:0;padding:5px 13px 5px 5px;border:1px solid #e5d8c7;border-radius:32px;background:#fffaf3;box-shadow:none}
@media(max-width:760px){body.cel-page .cel-book-editorial .cel-book-description{gap:13px}body.cel-page .cel-book-editorial .cel-book-actions{justify-content:flex-start}}
/* Album jeunesse : couverture, présentation et mémo épinglé. */
body.cel-page .cel-book-editorial .cel-books-layout{max-width:1250px;gap:clamp(20px,3vw,45px);grid-template-columns:minmax(0,.9fr) minmax(0,1.25fr)}
body.cel-page .cel-book-editorial .cel-books-layout.cel-has-book-note{grid-template-columns:minmax(0,.9fr) minmax(0,1.2fr) minmax(0,.85fr)}
body.cel-page .cel-book-editorial .cel-book-description{gap:12px}
body.cel-page .cel-book-editorial .cel-home-book-ribbon{display:inline-block;background:#e98932;color:#fff;padding:5px 18px 5px 12px;clip-path:polygon(0 0,100% 0,94% 50%,100% 100%,0 100%);font-size:13px;font-weight:700;border-radius:4px}
body.cel-page .cel-book-editorial .cel-book-facts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px 8px;padding:12px 0;border:0}
body.cel-page .cel-book-editorial .cel-book-facts>span{align-items:center;text-align:center;gap:3px;padding:0}
body.cel-page .cel-book-editorial .cel-book-facts i{display:block;color:#e98932;height:24px}
body.cel-page .cel-book-editorial .cel-book-facts i svg{width:24px;height:24px;stroke:currentColor;stroke-width:1.7;fill:none;stroke-linecap:round;stroke-linejoin:round}
body.cel-page .cel-book-editorial .cel-book-facts>span>strong{font-size:10px;letter-spacing:.04em}
body.cel-page .cel-book-editorial .cel-book-facts>span>span{font-size:12px;line-height:1.35}
body.cel-page .cel-book-editorial .cel-home-book-note{position:relative;align-self:center;background:linear-gradient(135deg,#fff6db,#fff0c5);border:1px solid #e8c98e;border-radius:15px;padding:26px 20px 22px;box-shadow:0 9px 18px #8052231d;transform:rotate(-2deg);color:#633619}
body.cel-page .cel-book-editorial .cel-note-pin{position:absolute;width:30px;height:36px;top:-19px;left:50%;transform:translateX(-50%) rotate(12deg);filter:drop-shadow(1px 2px 2px #84522144)}
body.cel-page .cel-book-editorial .cel-note-pin svg{display:block;width:100%;height:100%}
body.cel-page .cel-book-editorial .cel-home-book-note h3{font:700 23px/1.2 'Baloo 2',sans-serif;color:#633619;margin:0 0 14px;display:flex;align-items:center;justify-content:space-between;gap:8px}
body.cel-page .cel-book-editorial .cel-home-book-note h3 svg{width:25px;height:25px;fill:none;stroke:#e98932;stroke-width:1.8;flex:none}
body.cel-page .cel-book-editorial .cel-home-book-note ul{list-style:none;padding:0;margin:0;display:grid;gap:10px}
body.cel-page .cel-book-editorial .cel-home-book-note li{position:relative;padding-left:23px;font-size:14px;line-height:1.45}
body.cel-page .cel-book-editorial .cel-home-book-note li:before{content:"✓";position:absolute;left:0;top:0;color:#d77929;font-weight:900;font-size:19px}
@media(max-width:1050px){body.cel-page .cel-book-editorial .cel-books-layout.cel-has-book-note{grid-template-columns:minmax(0,.85fr) minmax(0,1.15fr)}body.cel-page .cel-book-editorial .cel-home-book-note{grid-column:2;max-width:440px;width:100%;margin-top:12px}}
@media(max-width:760px){body.cel-page .cel-book-editorial .cel-books-layout,body.cel-page .cel-book-editorial .cel-books-layout.cel-has-book-note{grid-template-columns:1fr}body.cel-page .cel-book-editorial .cel-home-book-note{grid-column:auto;justify-self:center;margin:20px 0}body.cel-page .cel-book-editorial .cel-book-description{width:100%}}
body.cel-page .cel-book-editorial .cel-book-quick-facts{display:flex;flex-wrap:wrap;align-items:center;gap:6px 0;color:#94603c;font-size:13px;line-height:1.5;margin:0 0 5px}
body.cel-page .cel-book-editorial .cel-book-quick-facts>span{display:inline-flex;gap:4px;align-items:baseline}
body.cel-page .cel-book-editorial .cel-book-quick-facts>span+span:before{content:"·";margin:0 9px;color:#e98932;font-weight:700}
body.cel-page .cel-book-editorial .cel-book-classification{display:grid;gap:6px;width:100%;font-size:14px;line-height:1.5;color:#633619;padding:12px 0;border-top:1px solid #e5d8c7}
body.cel-page .cel-book-editorial .cel-book-classification strong{font-weight:700;color:#94603c}
body.cel-page .cel-book-editorial .cel-book-classification a{color:#a75721;text-decoration:underline;text-underline-offset:3px}
body.cel-page .cel-book-editorial .cel-book-cover-audio{position:absolute!important;top:18px;left:19px;z-index:5;display:block;width:auto;height:auto;padding:0;margin:0;border:0;background:transparent;box-shadow:none;transform:none}
body.cel-page .cel-book-editorial .cel-book-cover-audio .cel-audio-toggle{position:relative!important;inset:auto!important;display:block;width:58px;height:58px;padding:0;border:0;border-radius:50%;background:#fffaf3;box-shadow:0 3px 12px #63361945;transform:none!important;cursor:pointer}
body.cel-page .cel-book-editorial .cel-book-cover-audio .cel-audio-toggle svg{display:block;width:100%;height:100%}
body.cel-page .cel-book-editorial .cel-book-cover-audio .cel-audio-toggle:hover{box-shadow:0 0 0 3px #e9893255,0 4px 12px #63361945}
body.cel-page .cel-book-editorial .cel-book-cover-audio .cel-audio-error[hidden]{display:none}
body.cel-page .cel-book-editorial .cel-book-cover-audio .cel-audio-error:not([hidden]){position:absolute;top:100%;left:0;width:max-content;background:#fffaf3;padding:5px 8px;border-radius:5px;font-size:12px}
@media(max-width:760px){body.cel-page .cel-book-editorial .cel-book-cover-audio{left:calc(50% - 130px)}body.cel-page .cel-book-editorial .cel-book-cover-audio .cel-audio-toggle{width:48px;height:48px}}
body.cel-page .cel-book-editorial .cel-book-audio-unavailable button{display:block;width:58px;height:58px;border:0;padding:0;border-radius:50%;background:#fffaf3;box-shadow:0 3px 12px #63361945;cursor:default;opacity:.85}body.cel-page .cel-book-editorial .cel-book-audio-unavailable button svg{width:100%;height:100%;display:block}body.cel-page .cel-book-editorial .cel-book-audio-unavailable>span{display:block;font-size:10px;color:#633619;background:#fffaf3;padding:3px 7px;border-radius:12px;margin-top:4px;width:max-content}
body.cel-page .cel-book-editorial .cel-book-heading-row{display:flex;align-items:center;justify-content:space-between;gap:14px;width:100%}
body.cel-page .cel-book-editorial .cel-book-heading-copy{flex:1;min-width:0}
body.cel-page .cel-book-editorial .cel-book-heading-copy .cel-book-quick-facts{margin-top:10px}
body.cel-page .cel-book-editorial .cel-book-heading-row .cel-book-cover-audio{position:relative!important;top:auto!important;left:auto!important;right:auto!important;flex:0 0 auto;align-self:center;text-align:center}
body.cel-page .cel-book-editorial .cel-book-heading-row .cel-book-audio-unavailable>span{white-space:normal;max-width:85px;margin:4px auto 0}
body.cel-page .cel-book-editorial .cel-book-heading-row .cel-book-audio-unavailable button{margin:auto}
@media(max-width:760px){body.cel-page .cel-book-editorial .cel-book-heading-row{gap:8px}body.cel-page .cel-book-editorial .cel-book-heading-row .cel-book-audio-unavailable button{width:48px;height:48px}}

/* Animation douce du livre, indépendante du survol. */
@keyframes cel-home-book-float {
  0%,100% {transform:translateY(0) rotateY(9deg) rotateX(0deg)}
  50% {transform:translateY(-10px) rotateY(19deg) rotateX(2deg)}
}
@keyframes cel-home-book-shadow {
  0%,100% {opacity:1;transform:translateX(-50%) scaleX(1)}
  50% {opacity:.65;transform:translateX(-50%) scaleX(.88)}
}
@media(prefers-reduced-motion:no-preference){
 body.cel-page .cel-book-editorial .cel-book-preview .cel-book-mockup{animation:cel-home-book-float 5s ease-in-out infinite}
 body.cel-page .cel-book-editorial .cel-book-preview:before{animation:cel-home-book-shadow 5s ease-in-out infinite}
}
@media(prefers-reduced-motion:reduce){
 body.cel-page .cel-book-editorial .cel-book-preview .cel-book-mockup,body.cel-page .cel-book-editorial .cel-book-preview:before{animation:none}
}

/* Garder le livre et la note en haut lorsque le résumé se déplie. */
body.cel-page .cel-book-editorial .cel-books-layout{align-items:start}
body.cel-page .cel-book-editorial .cel-book-preview,body.cel-page .cel-book-editorial .cel-home-book-note{align-self:start}
body.cel-page .cel-book-editorial .cel-book-preview .cel-home-cover-card{margin-top:0;margin-bottom:0}
body.cel-page .cel-book-editorial .cel-book-more summary{display:block}
body.cel-page .cel-book-editorial .cel-book-more[open] summary{display:none}
body.cel-page .cel-book-editorial .cel-book-more[open] .cel-book-full{display:block}
body.cel-page .cel-book-editorial .cel-book-collapse{display:none;background:none;border:0;padding:0;color:#a75721;font:700 14px/1.5 Nunito,sans-serif;text-decoration:underline;text-underline-offset:3px;cursor:pointer}
body.cel-page .cel-book-editorial .cel-book-more[open] .cel-book-collapse{display:inline-block}

.cel-identity-deck{padding:12px 14px 8px;color:#633619}
.cel-identity-stack{display:grid;position:relative;isolation:isolate;margin:0 10px 18px}
.cel-identity-card{grid-area:1/1;position:relative;min-width:0;background:#fffaf0;border:1px solid #dfb584;border-radius:17px;box-shadow:0 7px 14px #63361921;overflow:hidden;opacity:0;pointer-events:none;transform:translate(0,0);z-index:0}
.cel-identity-card:nth-child(1){opacity:1;pointer-events:auto;z-index:3}
.cel-identity-card:nth-child(2){opacity:1;transform:translate(8px,8px) rotate(2deg);z-index:2}
.cel-identity-card:nth-child(3){opacity:1;transform:translate(15px,15px) rotate(4deg);z-index:1}
.cel-identity-top{background:#e98932;color:#633619;display:flex;justify-content:space-between;gap:8px;padding:10px 14px;font:800 11px/1.4 Nunito,sans-serif;letter-spacing:.06em}
.cel-identity-body{display:grid;grid-template-columns:minmax(0,.85fr) minmax(0,1.15fr);gap:14px;padding:15px;align-items:center}
.cel-identity-portrait{background:#f5e6d3;border:1px solid #e8d0b5;border-radius:10px;aspect-ratio:3/4;overflow:hidden;display:grid;place-items:center}
.cel-identity-portrait img{width:100%;height:100%;object-fit:contain}
.cel-identity-body h3{font:700 25px/1.15 'Baloo 2',sans-serif;color:#633619;margin:0 0 9px}
.cel-identity-body p{font-size:13px;margin:0 0 5px;line-height:1.4}
.cel-identity-body p strong{display:block;font-size:10px;text-transform:uppercase;color:#a66a3c}
.cel-identity-body a{font-size:13px;display:inline-block;margin-top:7px}
.cel-identity-controls{display:flex;align-items:center;justify-content:center;gap:15px;margin:20px 0 8px;font-size:13px}
.cel-identity-controls button{border:1px solid #dfb584;background:#fffaf0;color:#633619;border-radius:50%;width:36px;height:36px;padding:0;font-size:26px;line-height:1;cursor:pointer}
.cel-identity-controls button:hover{background:#e98932;color:white}
@media(max-width:420px){.cel-identity-deck{padding-inline:0}.cel-identity-body{gap:8px;padding:10px}.cel-identity-body h3{font-size:20px}}

.cel-identity-portrait{aspect-ratio:1/1}
.cel-identity-body{grid-template-columns:minmax(0,.85fr) minmax(0,1.35fr);align-items:start}
.cel-identity-info{min-width:0}
.cel-identity-body .cel-identity-info h3{font-size:clamp(15px,1.35vw,23px);white-space:nowrap;letter-spacing:-.025em;margin-bottom:12px}
.cel-identity-pair{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.cel-identity-info p{margin-bottom:8px}
@media(max-width:480px){.cel-identity-body .cel-identity-info h3{white-space:normal;font-size:18px}}

.cel-identity-facts{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 14px;align-items:start}
.cel-identity-facts p{margin:0;overflow-wrap:anywhere}
.cel-identity-info>a{margin-top:14px}
.cel-identity-body .cel-identity-info h3{white-space:normal;overflow-wrap:normal;font-size:clamp(16px,1.4vw,23px)}

/* Transition ondulée vers la section suivante et contour orange clair des cartes. */
body.cel-page .cel-audio-feature>.cel-audio-wave:last-child{display:block!important;position:relative!important;width:100%;height:clamp(30px,4vw,65px)!important;margin:0 0 -1px;pointer-events:none;flex-shrink:0}
body.cel-page .cel-audio-feature>.cel-audio-wave:last-child path{fill:#fcf7ef}
body.cel-page .cel-audio-feature .cel-audio-card:hover,body.cel-page .cel-audio-feature .cel-audio-card:focus-within{border-color:#ffd08a!important;box-shadow:0 0 0 3px #ffd08a,0 9px 17px rgba(99,54,25,.21)!important}
body.cel-page .cel-audio-feature .cel-audio-card:hover .cel-audio-art,body.cel-page .cel-audio-feature .cel-audio-card:focus-within .cel-audio-art{box-shadow:none!important}

/* Remonter le titre et libérer davantage d’espace au-dessus des cartes. */
body.cel-page .cel-audio-feature{padding-top:clamp(24px,3.5vw,50px)!important}
body.cel-page .cel-audio-feature .cel-audio-inner{padding-top:8px;padding-bottom:24px}
body.cel-page .cel-audio-feature .cel-section-title{margin-top:0;margin-bottom:7px}
body.cel-page .cel-audio-feature .cel-audio-intro{margin-bottom:clamp(24px,3vw,42px)}

/* Ombre chaude et douce sous les cartes, même sans survol. */
body.cel-page .cel-audio-feature .cel-audio-grid>.cel-audio-card{box-shadow:0 5px 10px rgba(99,54,25,.16),0 12px 25px rgba(99,54,25,.22)}
body.cel-page .cel-audio-feature .cel-audio-grid>.cel-audio-card:hover,body.cel-page .cel-audio-feature .cel-audio-grid>.cel-audio-card:focus-within{box-shadow:0 0 0 3px #ffd08a,0 8px 14px rgba(99,54,25,.20),0 18px 30px rgba(99,54,25,.30)!important}

/* Halo lumineux discret autour du bouton lecture au repos. */
@keyframes cel-play-glimmer {
  0%,100% {box-shadow:0 3px 12px #63361944,0 0 0 0 rgba(255,208,138,.15)}
  50% {box-shadow:0 3px 12px #63361944,0 0 0 4px rgba(255,208,138,.65),0 0 11px 4px rgba(255,244,211,.65)}
}
@media(prefers-reduced-motion:no-preference){
 body.cel-page .cel-audio-feature .cel-audio-toggle[aria-pressed="false"]{animation:cel-play-glimmer 3s ease-in-out infinite!important}
 body.cel-page .cel-audio-feature .cel-audio-card:nth-child(2n) .cel-audio-toggle[aria-pressed="false"]{animation-delay:.7s!important}
}
body.cel-page .cel-audio-feature .cel-audio-toggle[aria-pressed="true"]{animation:none!important}

body.cel-page .cel-audio-feature .cel-section-title{font:700 clamp(29px,3vw,40px)/1.12 'Baloo 2',sans-serif!important;color:#fff!important}

.cel-identity-stack{overflow:visible;isolation:isolate}
.cel-identity-card{transform-origin:50% 55%;will-change:transform}
.cel-identity-card:nth-child(2){transform:translate(9px,9px) rotate(2.5deg)}
.cel-identity-card:nth-child(3){transform:translate(17px,17px) rotate(4.5deg)}
@media(prefers-reduced-motion:reduce){.cel-identity-card{will-change:auto}}

body.cel-page .cel-identity-deck .cel-identity-controls{display:flex!important;visibility:visible!important;position:relative;z-index:10;justify-content:center;align-items:center;gap:18px;margin:27px 0 12px}
body.cel-page .cel-identity-deck .cel-identity-controls button{display:grid!important;place-items:center;width:44px;height:44px;flex:0 0 44px;border:1px solid #d87928;background:#e98932;color:#fff;border-radius:50%;box-shadow:0 3px 8px #63361925;cursor:pointer;padding:0}
body.cel-page .cel-identity-deck .cel-identity-controls button:hover{background:#633619;border-color:#633619;color:white}
body.cel-page .cel-identity-deck .cel-identity-controls button:focus-visible{outline:2px solid #633619;outline-offset:3px}
.cel-deck-position{display:grid;text-align:center;color:#633619;line-height:1.35;font-weight:700}.cel-deck-position small{font-size:11px;font-weight:500;color:#a76129}

body.cel-page .cel-identity-deck .cel-identity-controls{gap:10px;margin:22px 0 4px}
body.cel-page .cel-identity-deck .cel-identity-controls button{width:28px;height:28px;flex:0 0 28px;background:transparent;color:#a76129;border:1px solid #dfb584;box-shadow:none}
body.cel-page .cel-identity-deck .cel-identity-controls button svg{width:15px;height:15px}
body.cel-page .cel-identity-deck .cel-identity-controls button:hover{background:#e98932;color:#fff;border-color:#e98932}
.cel-deck-position{font-size:11px;font-weight:600;color:#a76129}

.cel-identity-photo-column{min-width:0}
.cel-identity-location{display:flex;align-items:center;justify-content:center;gap:4px;margin-top:7px;color:#a76129;font-size:12px;line-height:1.4;font-weight:600}
.cel-identity-location svg{flex:none}

/* Léger mouvement de la pile au survol, indépendant du changement de carte. */
@media(hover:hover) and (prefers-reduced-motion:no-preference){
 .cel-identity-stack{transition:transform .32s cubic-bezier(.2,.7,.3,1)}
 .cel-identity-stack:hover{transform:translateY(-5px) rotate(-.65deg)}
 .cel-identity-card{transition:box-shadow .32s ease}
 .cel-identity-stack:hover>.cel-identity-card:first-child{box-shadow:0 12px 25px #63361930}
}

/* Ouverture légère en éventail de part et d'autre de la carte principale. */
@media(hover:hover) and (prefers-reduced-motion:no-preference){
 .cel-identity-stack>.cel-identity-card{transition:transform .36s cubic-bezier(.2,.7,.3,1),box-shadow .32s ease}
 .cel-identity-stack:hover{transform:translateY(-4px)}
 .cel-identity-stack:hover>.cel-identity-card:first-child{transform:translateY(-3px) rotate(-1deg)}
 .cel-identity-stack:hover>.cel-identity-card:nth-child(2){transform:translate(-12px,10px) rotate(-4deg)}
 .cel-identity-stack:hover>.cel-identity-card:nth-child(3){transform:translate(20px,12px) rotate(5deg)}
 .cel-identity-stack:has(>.cel-identity-card:nth-child(2):last-child):hover>.cel-identity-card:first-child{transform:translate(-4px,-3px) rotate(-2deg)}
 .cel-identity-stack:has(>.cel-identity-card:nth-child(2):last-child):hover>.cel-identity-card:nth-child(2){transform:translate(15px,9px) rotate(4deg)}
 .cel-identity-stack.cel-deck-turning>.cel-identity-card{transition:none}
 .cel-identity-stack.cel-deck-turning:hover>.cel-identity-card:first-child{transform:translate(0,0)}
 .cel-identity-stack.cel-deck-turning:hover>.cel-identity-card:nth-child(2){transform:translate(9px,9px) rotate(2.5deg)}
 .cel-identity-stack.cel-deck-turning:hover>.cel-identity-card:nth-child(3){transform:translate(17px,17px) rotate(4.5deg)}
}
.cel-character-intro{color:#80532b;font-size:15px;line-height:1.5;margin:0 0 12px}

.cel-play-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;padding:12px 6px 8px}
.cel-play-grid.cel-play-single{grid-template-columns:minmax(0,280px);justify-content:center}
.cel-play-card{display:block;min-width:0;padding:8px;background:#fffdfa;border:1px solid #ead5bb;border-radius:15px;box-shadow:0 5px 14px #63361918;text-decoration:none;color:#633619;transition:transform .25s ease,box-shadow .25s ease}
.cel-play-paper{position:relative;aspect-ratio:1/1;border-radius:9px;overflow:hidden;background:#f5e6d3}
.cel-play-paper img{width:100%;height:100%;object-fit:contain;display:block}
.cel-play-tag{position:absolute;top:8px;left:8px;background:#fffaf0;color:#925222;border-radius:20px;padding:3px 9px;font-size:11px;font-weight:700}
.cel-play-caption{display:flex;align-items:center;justify-content:space-between;gap:7px;padding:9px 4px 3px}
.cel-play-caption h3{font:700 17px/1.2 'Baloo 2',sans-serif;margin:0;color:#633619}
.cel-play-arrow{font-size:20px;color:#a76129}
.cel-play-all{display:block;text-align:center;color:#a76129;font-weight:700;font-size:14px;margin:14px 0 4px;text-decoration:none}
@media(hover:hover) and (prefers-reduced-motion:no-preference){.cel-play-card:hover{transform:translateY(-4px) rotate(-1deg);box-shadow:0 9px 20px #63361928}}

/* Une double rubrique éditoriale légère, sans grands encadrés. */
body.cel-page .cel-two-columns{width:min(1200px,94%);grid-template-columns:minmax(0,1fr) minmax(0,1.35fr);gap:20px;margin:40px auto;align-items:start}
body.cel-page .cel-two-columns>.cel-info-panel{background:transparent;box-shadow:none;border:0;border-radius:0;padding:24px;min-width:0}
body.cel-page .cel-two-columns .cel-section-title.small{font:700 clamp(25px,2.6vw,35px)/1.15 'Baloo 2',sans-serif;color:#633619;margin:0 0 12px}
body.cel-page .cel-two-columns .cel-character-intro{margin:0 0 20px;max-width:48ch;min-height:3em}
body.cel-page .cel-two-columns .cel-identity-deck{padding:12px 14px 8px}
body.cel-page .cel-two-columns .cel-identity-stack{margin:0 10px 18px}
body.cel-page .cel-two-columns .cel-play-grid{padding:12px 6px 8px;gap:16px}
body.cel-page .cel-two-columns .cel-play-grid.cel-play-single{grid-template-columns:minmax(0,280px);justify-content:center}
body.cel-page .cel-two-columns .cel-play-all{text-align:center;margin-top:14px}
@media(min-width:1001px){body.cel-page .cel-two-columns>.cel-play-panel{position:relative}body.cel-page .cel-two-columns>.cel-play-panel:before{content:'';position:absolute;left:-10px;top:10px;bottom:0;border-left:1px solid #e8d5bd}}
@media(max-width:1000px){body.cel-page .cel-two-columns{grid-template-columns:1fr;gap:42px;max-width:640px;margin:40px auto}body.cel-page .cel-two-columns .cel-character-intro{min-height:0}body.cel-page .cel-two-columns .cel-play-grid.cel-play-single{justify-content:center}body.cel-page .cel-two-columns .cel-play-all{text-align:center}}

/* Activités : petites vignettes carrées, sans modifier les cartes personnages. */
body.cel-page .cel-two-columns .cel-play-grid{grid-template-columns:repeat(2,minmax(0,180px));justify-content:center;gap:14px}
body.cel-page .cel-two-columns .cel-play-grid.cel-play-single{grid-template-columns:minmax(0,180px);justify-content:center}
body.cel-page .cel-play-card{padding:6px;border-radius:12px}
body.cel-page .cel-play-caption{padding:7px 3px 2px}
body.cel-page .cel-play-caption h3{font-size:15px}
@media(max-width:450px){body.cel-page .cel-two-columns .cel-play-grid{grid-template-columns:repeat(2,minmax(0,145px))}body.cel-page .cel-two-columns .cel-play-grid.cel-play-single{grid-template-columns:minmax(0,165px)}}

.cel-play-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 12px;align-items:center}
.cel-play-tabs button{border:1px solid #dfb584;background:transparent;color:#a76129;border-radius:20px;padding:5px 14px;font:600 13px/1.5 Nunito,sans-serif;cursor:pointer;transition:background .2s,color .2s}
.cel-play-tabs button[aria-pressed=true],.cel-play-tabs button:hover{background:#e98932;color:#fff;border-color:#e98932}
.cel-play-panel [hidden]{display:none!important}
.cel-play-filter-empty{font-size:13px;color:#80532b;text-align:center;padding:15px 0}

body.cel-page .cel-origin-section{position:relative;isolation:isolate;background:#f2e4d0;margin:55px 0 0;min-height:clamp(255px,24vw,380px);overflow:hidden;display:flex;align-items:center}
body.cel-page .cel-origin-bg{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:-2}
body.cel-page .cel-origin-section:before{content:'';position:absolute;inset:0;background:linear-gradient(90deg,#f2e4d0 0%,#f2e4d0f5 35%,#f2e4d055 100%);z-index:-1}
body.cel-page .cel-origin-inner{width:min(1200px,92%);margin:auto;padding:48px 0 65px}
body.cel-page .cel-origin-copy{width:46%;max-width:540px;color:#633619}
body.cel-page .cel-origin-copy h2{font:700 clamp(29px,3vw,40px)/1.12 'Baloo 2',sans-serif;margin:0 0 13px;color:#633619}
body.cel-page .cel-origin-copy p{font-size:16px;line-height:1.65;margin:0 0 22px;color:#80532b}
body.cel-page .cel-origin-button{display:inline-flex;align-items:center;border-radius:24px;padding:10px 19px;background:#e98932;color:white;text-decoration:none;font-weight:700;font-size:15px}
body.cel-page .cel-origin-pending{opacity:.75}
body.cel-page .cel-origin-button:hover{background:#633619;color:#fff}
body.cel-page .cel-footer.cel-footer-fox{margin-top:0!important}
body.cel-page .cel-footer-fox>.cel-fox-divider{left:auto!important;right:4%!important;transform:none!important;z-index:2!important}
@media(max-width:720px){body.cel-page .cel-origin-copy{width:100%;max-width:none}body.cel-page .cel-origin-inner{padding:35px 0 calc(15vw + 28px)}body.cel-page .cel-origin-section{margin-top:35px}body.cel-page .cel-origin-section:before{background:#f2e4d0e8}}

body.cel-page .cel-play-tag{background:#e98932;color:#fff;border:1px solid #fff3df;box-shadow:0 2px 7px #63361935;font-size:12px;font-weight:800;padding:5px 10px;top:7px;left:7px}
body.cel-page .cel-play-caption{align-items:center;padding:9px 3px 5px}
.cel-play-text{min-width:0;flex:1}
body.cel-page .cel-play-caption h3{font-size:16px;line-height:1.2;font-weight:700}
body.cel-page .cel-play-detail{font:500 12px/1.4 Nunito,sans-serif;color:#a76129;margin:4px 0 0}

body.cel-page .cel-origin-section>.cel-origin-wave{position:absolute;top:-1px;left:0;width:100%;height:clamp(24px,3.5vw,55px);display:block;z-index:1;pointer-events:none}
body.cel-page .cel-origin-inner{padding-top:clamp(60px,6vw,90px)}
</style>

</head>
<body <?php body_class('cel-page'); ?>>
<?php wp_body_open(); ?>
<a class="cel-skip" href="#cel-main">Aller au contenu</a>
<div class="cel-site">
<?php require __DIR__.'/interior-header.php'; ?>
<main id="cel-main">
<?php
$hero_below=($options['hero_text_location']??'overlay')==='below';
$hero_style='--cel-hero-title-size:'.max(24,min(100,(int)$options['hero_title_size'])).'px;--cel-hero-text-size:'.max(12,min(32,(int)$options['hero_text_size'])).'px;--cel-hero-x:'.max(0,min(100,(int)$options['hero_x'])).'%;--cel-hero-y:'.max(0,min(100,(int)$options['hero_y'])).'%;--cel-hero-width:'.max(240,min(1100,(int)$options['hero_width'])).'px;';
?>
<?php if($news): ?>
<section id="cel-decouvrir" class="cel-feature-slider" aria-label="Les nouveautés" aria-roledescription="carrousel">
    <?php foreach($news as $idx=>$item):
      $kind = [
        'cel_coloriage' => 'Activité',
        'cel_personnage' => 'Personnage',
        'cel_livre' => 'Livre',
        'cel_histoire' => 'Mini-histoire audio',
      ][$item->post_type] ?? 'Découverte';
      // Illustration de la fiche uniquement : ne pas présenter une autre illustration à sa place.
      $img = get_the_post_thumbnail_url($item->ID, 'medium_large');
      $preview=$item->post_type==='cel_histoire'?(Celestin_Fields::value($item->ID,'resume_audio')?:Celestin_Fields::value($item->ID,'audio')):($item->post_type==='cel_personnage'?Celestin_Fields::value($item->ID,'audio'):'');
      $title=get_the_title($item);
      $new_label=['cel_coloriage'=>'Nouvelle activité','cel_personnage'=>'Nouveau personnage','cel_livre'=>'Nouveau livre','cel_histoire'=>'Nouvelle mini-histoire audio'][$item->post_type]??'Nouvelle découverte';
    ?>

<?php
$summary=Celestin_Fields::value($item->ID,'resume');
if(!$summary && $item->post_type==='cel_personnage')$summary=Celestin_Fields::value($item->ID,'description');
$summary=$summary?:$item->post_excerpt;
$kicker=$item->post_type==='cel_coloriage'?(Celestin_Fields::value($item->ID,'type')?:'Activité'):($item->post_type==='cel_personnage'?'':$new_label);
$story_tags=[];
if($item->post_type==='cel_histoire'){
 $themes=wp_get_object_terms($item->ID,Celestin_Library::TAX['categorie'],['fields'=>'names']);
 $kicker=(!is_wp_error($themes)&&$themes)?implode(' · ',$themes):'Mini-histoire audio';
 $labels=['collection'=>'Collection','saison'=>'Saison','mois'=>'Parution','age_lecture'=>'Âge','evenement'=>'Événement'];
 foreach($labels as $key=>$label){
  $terms=wp_get_object_terms($item->ID,Celestin_Library::TAX[$key],['fields'=>'names']);
  if(is_wp_error($terms)||!$terms)continue;
  if($key==='age_lecture'){
   $ranges=[];$other=[];
   foreach($terms as $term){if(preg_match('/^(\\d+)\\s*[-–]\\s*(\\d+)(?:\\s*ans)?$/u',trim($term),$m))$ranges[]=[(int)$m[1],(int)$m[2]];else $other[]=$term;}
   usort($ranges,fn($a,$b)=>$a[0]<=>$b[0]);$merged=[];
   foreach($ranges as $range){$last=count($merged)-1;if($last>=0&&$range[0]<=$merged[$last][1])$merged[$last][1]=max($merged[$last][1],$range[1]);else $merged[]=$range;}
   $terms=array_merge(array_map(fn($range)=>$range[0].'–'.$range[1].' ans',$merged),$other);
  }
  $story_tags[]=$label.' : '.implode(', ',$terms);
 }
 if(!$summary)$summary=Celestin_Fields::value($item->ID,'texte');
}
$cta=['cel_histoire'=>'Découvrir cette mini-histoire','cel_livre'=>'Découvrir ce livre','cel_coloriage'=>'Découvrir cette activité','cel_personnage'=>'Rencontrer ce personnage'][$item->post_type]??'Découvrir';
?>
<div class="cel-feature-slide" <?php if($idx!==0)echo 'hidden'; ?> data-slide="<?php echo esc_attr($idx); ?>">
 <?php if($img): ?><div class="cel-feature-backdrop" aria-hidden="true"><img src="<?php echo esc_url(get_the_post_thumbnail_url($item->ID,'large')?:$img); ?>" alt="" decoding="async"></div><?php endif; ?>
 <div class="cel-feature-layout">
       <article class="cel-card cel-news-card cel-news-<?php echo esc_attr($item->post_type); ?>">
        <span class="cel-card-type cel-news-badge"><?php echo esc_html($new_label); ?></span>
        <div class="cel-card-image">
         <?php if($item->post_type==='cel_coloriage' && $img): ?><div class="cel-paper-stack" aria-hidden="true"><img src="<?php echo esc_url($img); ?>" alt=""><img src="<?php echo esc_url($img); ?>" alt=""></div><?php endif; ?>
         <a class="cel-news-image-link" href="<?php echo esc_url(get_permalink($item)); ?>" aria-label="<?php echo esc_attr($title); ?>">
         <?php if($img): ?><img src="<?php echo esc_url($img); ?>" alt="" loading="lazy"><?php else: ?><div class="cel-card-placeholder" aria-hidden="true">✦</div><?php endif; ?>
         </a>
         
         <?php if($preview): ?>
         <audio preload="metadata" src="<?php echo esc_url($preview); ?>"></audio>
         <button type="button" class="cel-audio-toggle" aria-label="<?php echo esc_attr(($item->post_type==='cel_personnage'?'Écouter la présentation de ':'Écouter ').$title); ?>" data-title="<?php echo esc_attr($title); ?>" aria-pressed="false">
         <svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#fffaf3"/><circle cx="32" cy="32" r="26" fill="none" stroke="#e9d3bb" stroke-width="4"/><circle class="cel-audio-ring" cx="32" cy="32" r="26" fill="none" stroke="#e98932" stroke-width="4" pathLength="100" stroke-dasharray="0 100" stroke-linecap="round" transform="rotate(-90 32 32)"/><path class="cel-audio-play" d="M25 20L44 32L25 44Z" fill="#a45b2c"/><rect class="cel-audio-stop" x="23" y="23" width="18" height="18" rx="3" fill="#a45b2c"/></svg>
         </button><span class="cel-audio-error" role="status" hidden>Audio indisponible.</span>
         <?php endif; ?>
        </div>
      </article>
 <div class="cel-feature-copy"><?php if($kicker): ?><p class="cel-feature-kicker"><?php echo esc_html($kicker); ?></p><?php endif; ?><h2><?php echo esc_html($title); ?></h2>
 <?php if($story_tags): ?><div class="cel-feature-tags"><?php foreach($story_tags as $tag): ?><span><?php echo esc_html($tag); ?></span><?php endforeach; ?></div><?php endif; ?>
 <?php if($summary): ?><p class="cel-feature-summary"><?php echo esc_html(wp_trim_words(wp_strip_all_tags($summary),$item->post_type==='cel_personnage'?38:65,'…')); ?></p><?php endif; ?>
<?php if($item->post_type==='cel_personnage'): ?>
<div class="cel-feature-character-facts">
<?php foreach(['anniversaire'=>['Anniversaire','<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>'],'age'=>['Âge','<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],'espece'=>['Espèce','<path d="M4 4l5 3h6l5-3v9l-8 7-8-7z"/><path d="M9 12h.01M15 12h.01M10 16h4"/>']] as $key=>$fact): $value=Celestin_Fields::value($item->ID,$key); if($value): ?>
<div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo $fact[1]; ?></svg><span><strong><?php echo esc_html($fact[0]); ?></strong> : <?php echo esc_html($value); ?></span></div>
<?php endif; endforeach; ?>
</div>
<?php endif; ?>
        <?php if($item->post_type==='cel_histoire'): ?><div class="cel-feature-audio-info"><?php if($preview): ?><div class="cel-feature-duration"><svg width="16" height="16" style="width:16px!important;height:16px!important;max-width:16px!important;max-height:16px!important;flex:0 0 16px!important;display:inline-block!important" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span class="cel-time-total">--:--</span></div><?php elseif($item->post_type==='cel_histoire'): ?><span>Audio bientôt disponible</span><?php endif; ?></div><?php endif; ?>
 <a class="cel-feature-link" href="<?php echo esc_url(get_permalink($item)); ?>"><?php echo esc_html($cta); ?> →</a></div>
 </div>
</div>
<?php endforeach; ?>
<?php if(count($news)>1): ?><div class="cel-feature-pagination" aria-label="Choisir une nouveauté"><?php foreach($news as $idx=>$item): ?><button type="button" data-slide="<?php echo esc_attr($idx); ?>" aria-label="<?php echo esc_attr(get_the_title($item)); ?>" aria-current="<?php echo $idx===0?'true':'false'; ?>"></button><?php endforeach; ?></div><?php endif; ?>
</section>
<?php else: ?>
<section class="cel-hero <?php echo $hero_below?'cel-hero-below':'cel-hero-overlay'; ?>" aria-labelledby="cel-hero-title" style="<?php echo esc_attr($hero_style); ?>">
 <div class="cel-hero-picture">
 <?php if(!empty($options['hero'])) echo wp_get_attachment_image((int)$options['hero'],'full',false,['class'=>'cel-hero-art','alt'=>'L’univers illustré de Célestin et de Fleuriville','fetchpriority'=>'high','loading'=>'eager']); ?>
 </div>
 <div class="cel-hero-copy">
 <?php if($options['hero_intro']!==''): ?><p class="cel-eyebrow"><?php echo esc_html($options['hero_intro']); ?></p><?php endif; ?>
 <h1 id="cel-hero-title"><?php echo nl2br(esc_html($options['headline'])); ?></h1>
 <?php if($options['hero_description']!==''): ?><p class="cel-hero-description"><?php echo nl2br(esc_html($options['hero_description'])); ?></p><?php endif; ?>
 <?php if($options['hero_button']): ?><a class="cel-button cel-primary" href="<?php echo esc_url($options['hero_link']?:'#cel-decouvrir'); ?>"><?php echo esc_html($options['hero_button']); ?> <span aria-hidden="true">→</span></a><?php endif; ?>
 </div>
</section>

<?php endif; ?>

<section class="cel-section cel-books cel-book-editorial" aria-label="Livre à découvrir">
 <?php if($book):
 $book_resume=Celestin_Fields::value($book->ID,'resume');
 $book_short=wp_trim_words(wp_strip_all_tags($book_resume),30,'…');
 $book_audio=Celestin_Fields::value($book->ID,'resume_audio');
 $book_points=preg_split('/\R/u',trim((string)Celestin_Fields::value($book->ID,'points_livre')),-1,PREG_SPLIT_NO_EMPTY);
 $book_number=Celestin_Fields::value($book->ID,'numero'); $book_month=Celestin_Fields::value($book->ID,'mois'); $book_pages=absint(Celestin_Fields::value($book->ID,'nombre_pages'));
 ?>
 <div class="cel-books-layout<?php echo $book_points?' cel-has-book-note':''; ?>">
 <div class="cel-book-preview">
 <a class="cel-home-cover-card cel-book-mockup" href="<?php echo esc_url(get_permalink($book)); ?>" aria-label="<?php echo esc_attr('Découvrir '.$book->post_title); ?>">
 <span class="cel-book-pages" aria-hidden="true"></span><span class="cel-book-face"><?php echo Celestin_Public::illustration($book->ID,'large'); ?></span>
 </a>

 </div>
 <div class="cel-book-description">
 <div class="cel-book-heading-row"><div class="cel-book-heading-copy">
 <h2 class="cel-book-editorial-title"><a href="<?php echo esc_url(get_permalink($book)); ?>"><?php echo esc_html($book->post_title); ?></a></h2>
 <div class="cel-book-quick-facts">
 <?php if($book_month): ?><span><strong>Date de sortie :</strong> <?php echo esc_html($book_month.(preg_match('/\b\d{4}\b/u',$book_month)?'':' 2027')); ?></span><?php endif; ?>
 <?php $book_age=Celestin_Fields::value($book->ID,'age_lecture'); if($book_age): ?><span><strong>Âge :</strong> <?php echo esc_html($book_age); ?></span><?php endif; ?>
 <?php if($book_pages): ?><span><?php echo esc_html($book_pages); ?> pages</span><?php endif; ?>
 </div>
 </div>
 <?php if($book_audio): ?><div class="cel-book-cover-audio cel-audio-card"><audio preload="metadata" src="<?php echo esc_url($book_audio); ?>"></audio><button type="button" class="cel-audio-toggle" data-title="<?php echo esc_attr($book->post_title); ?>" aria-label="Écouter le résumé ou un extrait" title="Écouter un extrait" aria-pressed="false"><svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#fffaf3"/><circle cx="32" cy="32" r="26" fill="none" stroke="#e9d3bb" stroke-width="4"/><circle class="cel-audio-ring" cx="32" cy="32" r="26" fill="none" stroke="#e98932" stroke-width="4" pathLength="100" stroke-dasharray="0 100" stroke-linecap="round" transform="rotate(-90 32 32)"/><path class="cel-audio-play" d="M25 20L44 32L25 44Z" fill="#a45b2c"/><rect class="cel-audio-stop" x="23" y="23" width="18" height="18" rx="3" fill="#a45b2c"/></svg></button><span class="cel-audio-error" role="status" hidden>Audio indisponible.</span></div><?php else: ?><div class="cel-book-cover-audio cel-book-audio-unavailable"><button type="button" disabled aria-label="Extrait audio bientôt disponible" title="Extrait audio bientôt disponible"><svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#fffaf3"/><circle cx="32" cy="32" r="26" fill="none" stroke="#e98932" stroke-width="3"/><path d="M25 20L44 32L25 44Z" fill="#a45b2c"/></svg></button><span>Audio bientôt disponible</span></div><?php endif; ?>
 </div>
 <?php if($book_resume): ?><div class="cel-home-book-summary"><?php if($book_short!==wp_strip_all_tags($book_resume) && mb_strlen($book_short)<mb_strlen(wp_strip_all_tags($book_resume))): ?><details class="cel-book-more"><summary><span class="cel-book-excerpt"><?php echo esc_html($book_short); ?></span><span class="cel-book-more-label"><span class="cel-book-open">Lire plus +</span><span class="cel-book-close">Lire moins −</span></span></summary><div class="cel-book-full"><?php echo wpautop(esc_html($book_resume)); ?><button type="button" class="cel-book-collapse" onclick="this.closest('details').open=false;this.closest('details').querySelector('summary').focus()">Lire moins −</button></div></details><?php else: ?><p><?php echo esc_html($book_resume); ?></p><?php endif; ?></div><?php endif; ?>
 <div class="cel-book-classification">
 <?php foreach(['collection'=>'Collection','saison'=>'Saison','evenement'=>'Événement','categorie'=>'Thème'] as $key=>$label): $value=Celestin_Fields::value($book->ID,$key); if(!$value)continue; ?>
 <div><strong><?php echo esc_html($label); ?> :</strong> <?php if($key==='collection'): $terms=wp_get_object_terms($book->ID,Celestin_Library::TAX['collection']); if(!is_wp_error($terms)&&$terms): foreach($terms as $i=>$term): if($i)echo ', '; ?><a href="<?php echo esc_url(add_query_arg('filtre_collection',$term->slug,Celestin_Home::url('cel_livre'))); ?>"><?php echo esc_html($term->name); ?></a><?php endforeach; else: echo esc_html($value); endif; else: echo esc_html($value); endif; ?></div>
 <?php endforeach; ?>
 </div>

 <div class="cel-book-actions"><a class="cel-feature-link cel-home-book-all" href="<?php echo esc_url(get_permalink($book)); ?>">Découvrir ce livre →</a>
 <a class="cel-book-library-link" href="<?php echo esc_url(Celestin_Home::url('cel_livre')); ?>">Voir tous les livres <span aria-hidden="true">↗</span></a></div>
 </div>
 <?php if($book_points): ?><aside class="cel-home-book-note"><span class="cel-note-pin" aria-hidden="true"><svg viewBox="0 0 32 40"><path d="m16 24-5 13" stroke="#77533a" stroke-width="2" stroke-linecap="round"/><ellipse cx="16" cy="22" rx="10" ry="6" fill="#d77b30" stroke="#ad5b22" stroke-width="1.5"/><path d="m10 9-2 12q8 8 15 0L22 9" fill="#e98932" stroke="#ad5b22" stroke-width="1.5"/><ellipse cx="16" cy="9" rx="10" ry="7" fill="#f6aa56" stroke="#ad5b22" stroke-width="1.5"/></svg></span><h3>Dans ce livre… <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 21 3 12C-2 5 7 0 12 7c5-7 14-2 9 5Z"/></svg></h3><ul><?php foreach($book_points as $point): if(trim($point)==='')continue; ?><li><?php echo esc_html(trim($point)); ?></li><?php endforeach; ?></ul></aside><?php endif; ?>
 </div>
 <?php else: ?><p class="cel-home-empty">Sélectionne un livre depuis sa fiche WordPress pour l’afficher ici.</p><?php endif; ?>
</section>
<?php if($stories): ?>
<section class="cel-story-section cel-audio-feature" aria-labelledby="cel-story-title">

 <svg class="cel-audio-wave" viewBox="0 0 1440 70" preserveAspectRatio="none" aria-hidden="true"><path d="M0 0H1440V25C1100 85 850 0 540 36S120 66 0 24Z" fill="#fcf7ef"/></svg>
 <div class="cel-audio-inner">
 <h2 id="cel-story-title" class="cel-section-title">Les mini-histoires du moment</h2>
 <p class="cel-audio-intro">Installe-toi confortablement et laisse-toi raconter une histoire.</p>
 <div class="cel-audio-grid">
 <?php foreach($stories as $story):
 $title=get_the_title($story);$mp3=Celestin_Fields::value($story->ID,'resume_audio')?:Celestin_Fields::value($story->ID,'audio');
 $terms=get_the_terms($story->ID,Celestin_Library::TAX['categorie']);$theme=($terms&&!is_wp_error($terms))?$terms[0]->name:'';
 ?>
 <article class="cel-audio-card">
 <div class="cel-audio-art">
 <a href="<?php echo esc_url(get_permalink($story)); ?>" aria-label="<?php echo esc_attr($title); ?>">
 <?php if(has_post_thumbnail($story))echo get_the_post_thumbnail($story->ID,'large',['loading'=>'lazy','alt'=>'']);else echo '<div class="cel-illustration-placeholder" aria-hidden="true"><span>✦</span></div>'; ?>
 </a>
 <?php if($theme): ?><span class="cel-audio-theme"><?php echo esc_html($theme); ?></span><?php endif; ?>
 <?php if($mp3): ?>
 <audio preload="none" src="<?php echo esc_url($mp3); ?>"></audio>
 <button type="button" class="cel-audio-toggle" aria-label="<?php echo esc_attr('Écouter '.$title); ?>" data-title="<?php echo esc_attr($title); ?>" aria-pressed="false">
 <svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#fffaf3"/><circle cx="32" cy="32" r="26" fill="none" stroke="#e9d3bb" stroke-width="4"/><circle class="cel-audio-ring" cx="32" cy="32" r="26" fill="none" stroke="#e98932" stroke-width="4" pathLength="100" stroke-dasharray="0 100" stroke-linecap="round" transform="rotate(-90 32 32)"/><path class="cel-audio-play" d="M25 20L44 32L25 44Z" fill="#a45b2c"/><rect class="cel-audio-stop" x="23" y="23" width="18" height="18" rx="3" fill="#a45b2c"/></svg>
 </button><span class="cel-audio-error" role="status" hidden>Audio indisponible.</span>
 <?php endif; ?>
 </div><h3><a href="<?php echo esc_url(get_permalink($story)); ?>"><?php echo esc_html($title); ?></a></h3>
 </article>
 <?php endforeach; ?>
 <div class="cel-audio-cta"><a class="cel-audio-all" href="<?php echo esc_url(Celestin_Home::url('cel_histoire')); ?>">Découvrir toutes les mini-histoires →</a></div>
 </div>
 </div>
 <svg class="cel-audio-wave" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true"><path d="M0 32Q360 -12 720 32T1440 15V60H0Z" fill="#fcf7ef"/></svg>
</section>
<?php endif; ?>
<div class="cel-two-columns">
 <section class="cel-info-panel" aria-labelledby="cel-character-title">
   <h2 id="cel-character-title" class="cel-section-title small">Rencontre nos personnages</h2><p class="cel-character-intro">Fais connaissance avec les habitants de Fleuriville, découvre leurs petites habitudes, leurs secrets et les liens qui les unissent.</p>
   <?php
   $characters=get_posts(['post_type'=>'cel_personnage','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'ID','order'=>'ASC']);shuffle($characters);
   if($characters): ?>
   <div class="cel-identity-deck" data-cel-deck>
    <div class="cel-identity-stack" aria-live="polite">
    <?php foreach($characters as $index=>$person): ?>
     <article class="cel-identity-card" <?php if($index)echo 'inert aria-hidden="true"'; ?>>
      <div class="cel-identity-top"><span>CARTE D’IDENTITÉ</span><span><?php echo esc_html(sprintf("N° FV-%06d",$person->ID)); ?></span></div>
      <div class="cel-identity-body"><div class="cel-identity-photo-column"><div class="cel-identity-portrait"><?php echo Celestin_Public::illustration($person->ID,'medium_large'); ?></div><div class="cel-identity-location"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg><span>Fleuriville</span></div></div>
      <div class="cel-identity-info">
      <?php $name=trim(Celestin_Fields::value($person->ID,'prenom').' '.Celestin_Fields::value($person->ID,'nom')); $birthday=Celestin_Fields::value($person->ID,'anniversaire');$age=Celestin_Fields::value($person->ID,'age'); ?>
      <h3><?php echo esc_html($name?:get_the_title($person)); ?></h3>
      <div class="cel-identity-pair cel-identity-facts"><?php foreach(['espece'=>'Espèce','genre'=>'Genre'] as $key=>$label):$value=Celestin_Fields::value($person->ID,$key);if($value): ?><p><strong><?php echo esc_html($label); ?></strong> <?php echo esc_html($value); ?></p><?php endif;endforeach; ?>
      <?php if($birthday||$age): ?><p><strong><?php echo $birthday?'Anniversaire':'Âge'; ?></strong> <?php echo esc_html($birthday); ?><?php if($age): ?><?php echo esc_html(($birthday?' (':'').$age.(preg_match('/^\d+$/u',trim($age))?' ans':'').($birthday?')':'')); ?><?php endif; ?></p><?php endif; ?>
      <?php foreach(['role'=>'Rôle','pelage'=>'Pelage'] as $key=>$label):$value=Celestin_Fields::value($person->ID,$key);if($value): ?><p><strong><?php echo esc_html($label); ?></strong> <?php echo esc_html($value); ?></p><?php endif;endforeach; ?></div>
      <a class="cel-text-link" href="<?php echo esc_url(get_permalink($person)); ?>">Découvrir ma fiche →</a></div></div>
     </article>
    <?php endforeach; ?>
    </div>
    <?php if(count($characters)>1): ?><div class="cel-identity-controls"><button type="button" data-deck-prev aria-label="Personnage précédent" title="Personnage précédent"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m14 5-7 7 7 7"/></svg></button><span class="cel-deck-position"><span data-deck-count>1 / <?php echo count($characters); ?></span></span><button type="button" data-deck-next aria-label="Personnage suivant" title="Personnage suivant"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m10 5 7 7-7 7"/></svg></button></div><?php endif; ?>
   </div>
   <?php else: ?><p class="cel-home-empty">Les personnages seront bientôt à découvrir.</p><?php endif; ?>
   
 </section>
 <section class="cel-info-panel cel-play-panel" aria-labelledby="cel-play-title">
   <h2 id="cel-play-title" class="cel-section-title small">À toi de jouer !</h2>
   <p class="cel-character-intro">Sors tes crayons et laisse parler ton imagination avec les petites activités de Célestin.</p>
   <?php $activities=Celestin_Home::featured('cel_coloriage',4); ?>
   <div class="cel-play-tabs" role="group" aria-label="Type d’activité"><button type="button" data-play-filter="all" aria-pressed="true">Tout</button><button type="button" data-play-filter="coloriage" aria-pressed="false">Coloriages</button><button type="button" data-play-filter="jeu" aria-pressed="false">Jeux</button></div>
   <?php if($activities): ?>
   <div class="cel-play-grid<?php echo count($activities)===1?' cel-play-single':''; ?>">
   <?php foreach($activities as $act): $kind=Celestin_Fields::value($act->ID,'type');
    $themes=wp_get_object_terms($act->ID,Celestin_Library::TAX['categorie'],['fields'=>'names']);
    $events=wp_get_object_terms($act->ID,Celestin_Library::TAX['evenement'],['fields'=>'names']);
    if(is_wp_error($themes)||!$themes)$themes=wp_get_object_terms($act->ID,'cel_theme_cel_coloriage',['fields'=>'names']);
    $details=array_unique(array_merge(is_array($themes)?$themes:[],is_array($events)?$events:[])); ?>
    <a class="cel-play-card" data-play-type="<?php echo esc_attr(stripos($kind,'coloriage')!==false?'coloriage':'jeu'); ?>" href="<?php echo esc_url(get_permalink($act)); ?>">
      <div class="cel-play-paper">
        <?php echo Celestin_Public::illustration($act->ID,'medium_large'); ?>
        <?php if($kind): ?><span class="cel-play-tag"><?php echo esc_html($kind); ?></span><?php endif; ?>
      </div>
      <div class="cel-play-caption"><div class="cel-play-text"><h3><?php echo esc_html($act->post_title); ?></h3><?php if($details): ?><p class="cel-play-detail"><?php echo esc_html(implode(' · ',$details)); ?></p><?php endif; ?></div><span class="cel-play-arrow" aria-hidden="true">→</span></div>
    </a>
   <?php endforeach; ?>
   </div>
   <p class="cel-play-filter-empty" hidden>Aucune activité de ce type pour le moment.</p>
   <a class="cel-play-all" href="<?php echo esc_url(Celestin_Home::url('cel_coloriage')); ?>">Toutes les activités →</a>
   <?php else: ?><p class="cel-home-empty">De nouvelles activités arrivent bientôt !</p><?php endif; ?>
 </section>
</div>
<section class="cel-origin-section" aria-labelledby="cel-origin-title">
 <svg class="cel-origin-wave" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true"><path d="M0 0H1440V22C1200 58 950 58 720 28S240 4 0 35Z" fill="#fcf7ef"/></svg>
 <?php if(Celestin_Home::image('footer')) echo '<img class="cel-origin-bg" src="'.esc_url(Celestin_Home::image('footer')).'" alt="" loading="lazy">'; ?>
 <div class="cel-origin-inner"><div class="cel-origin-copy">
 <h2 id="cel-origin-title"><?php echo esc_html($options['origin_title']); ?></h2>
 <p><?php echo esc_html($options['origin_description']); ?></p>
 <?php $origin_link=$options['origin_link']; if(!$origin_link){$origin_page=get_page_by_path('les-origines-de-celestin');if($origin_page&&$origin_page->post_status==='publish')$origin_link=get_permalink($origin_page);} ?>
 <?php if($origin_link): ?><a class="cel-origin-button" href="<?php echo esc_url($origin_link); ?>"><?php echo esc_html($options['origin_button']); ?> →</a><?php else: ?><span class="cel-origin-button cel-origin-pending"><?php echo esc_html($options['origin_button']); ?> · Bientôt</span><?php endif; ?>
 </div></div>
</section>
</main>
<?php require __DIR__.'/shared-footer.php'; ?>
</div>
<script>
document.querySelectorAll('.cel-play-panel').forEach(panel=>{
 const buttons=panel.querySelectorAll('[data-play-filter]'),cards=Array.from(panel.querySelectorAll('[data-play-type]')),grid=panel.querySelector('.cel-play-grid'),empty=panel.querySelector('.cel-play-filter-empty');
 buttons.forEach(button=>button.addEventListener('click',()=>{
  const type=button.dataset.playFilter;buttons.forEach(b=>b.setAttribute('aria-pressed',String(b===button)));
  let count=0;cards.forEach(card=>{card.hidden=type!=='all'&&card.dataset.playType!==type;if(!card.hidden)count++;});
  if(grid)grid.classList.toggle('cel-play-single',count===1);if(empty)empty.hidden=count!==0;
 }));
});
document.querySelectorAll('[data-cel-deck]').forEach(deck=>{
 const stack=deck.querySelector('.cel-identity-stack'), counter=deck.querySelector('[data-deck-count]');
 const shuffled=Array.from(stack.children);
 // Mélange également côté navigateur pour les pages servies depuis un cache.
 for(let i=shuffled.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[shuffled[i],shuffled[j]]=[shuffled[j],shuffled[i]];}
 shuffled.forEach(card=>stack.append(card));
 const original=Array.from(stack.children);let busy=false;
 function refresh(){Array.from(stack.children).forEach((card,i)=>{card.inert=i!==0;if(i)card.setAttribute('aria-hidden','true');else card.removeAttribute('aria-hidden')});if(counter)counter.textContent=(original.indexOf(stack.firstElementChild)+1)+' / '+original.length;}
 async function turn(direction){
  if(busy||original.length<2)return;busy=true;stack.classList.add('cel-deck-turning');
  const reduced=matchMedia('(prefers-reduced-motion: reduce)').matches;
  const front=stack.firstElementChild,last=stack.lastElementChild;
  const animate=async(card,frames,options)=>{if(!reduced&&card.animate){const anim=card.animate(frames,options);try{await anim.finished}catch(e){}finally{anim.cancel()}}};
  const next=front.nextElementSibling;
  try{
   if(direction>0){
    if(!reduced&&front.animate){
     // La carte sort latéralement, passe derrière les autres puis rentre dans la pile.
     front.style.zIndex='5';
     await animate(front,[{transform:'translate(0,0) rotate(0deg)'},{transform:'translate(-55%, -5%) rotate(-12deg)'}],{duration:310,easing:'cubic-bezier(.35,0,.25,1)'});
     const backTransform=original.length===2?'translate(8px,8px) rotate(2deg)':'translate(15px,15px) rotate(4deg)';
     front.style.zIndex='0';front.style.transform='translate(-55%, -5%) rotate(-12deg)';
     stack.append(front);front.style.opacity='1';next.style.zIndex='3';
     await Promise.all([
      animate(front,[{transform:'translate(-55%, -5%) rotate(-12deg)',opacity:1},{transform:backTransform,opacity:1}],{duration:340,easing:'cubic-bezier(.2,.7,.2,1)'}),
      animate(next,[{transform:'translate(8px,8px) rotate(2deg)'},{transform:'translate(0,0) rotate(0deg)'}],{duration:340,easing:'ease-out'})
     ]);
    }else stack.append(front);
   }else{
    if(!reduced&&last.animate){
     last.style.zIndex='1';last.style.opacity='1';
     await animate(last,[{transform:original.length===2?'translate(8px,8px) rotate(2deg)':'translate(15px,15px) rotate(4deg)'},{transform:'translate(-55%, -5%) rotate(-12deg)'}],{duration:310,easing:'ease-in-out'});
     stack.prepend(last);last.style.zIndex='5';last.style.transform='translate(-55%, -5%) rotate(-12deg)';
     await animate(last,[{transform:'translate(-55%, -5%) rotate(-12deg)'},{transform:'translate(0,0) rotate(0deg)'}],{duration:340,easing:'ease-out'});
    }else stack.prepend(last);
   }
  }finally{
   original.forEach(card=>{card.style.removeProperty('z-index');card.style.removeProperty('transform');card.style.removeProperty('opacity')});refresh();busy=false;stack.classList.remove('cel-deck-turning');
  }
 }
 deck.querySelector('[data-deck-next]')?.addEventListener('click',()=>turn(1));deck.querySelector('[data-deck-prev]')?.addEventListener('click',()=>turn(-1));refresh();
});
</script>
<?php wp_footer(); ?>
</body></html>
