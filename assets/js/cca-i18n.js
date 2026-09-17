/**
 * CORE CALORIE ADVISOR — Internationalization (i18n) & Global Language Switcher
 * Provides client-side dynamic localization, RTL alignment for Arabic/Urdu,
 * keyboard accessibility, search filter, and universal worldwide translation coverage.
 */

(function () {
  'use strict';

  const STORAGE_KEY = 'cca_lang';
  const RTL_LANGS = ['ar', 'ur', 'fa', 'he'];

  const I18N_DICTIONARY = {
    es: {
      'Home': 'Inicio',
      'Workouts': 'Entrenamientos',
      'Nutrition': 'Nutrición',
      'Shop': 'Tienda',
      'Trainers': 'Entrenadores',
      'Pricing': 'Precios',
      'Calculators': 'Calculadoras',
      'Community': 'Comunidad',
      'Dashboard': 'Panel',
      'Login': 'Iniciar sesión',
      'Register': 'Registrarse',
      'Start Session': 'Iniciar sesión',
      'Open Scanner': 'Abrir escáner',
      'Unlock With Pro': 'Desbloquear con Pro',
      'Search workouts, nutrition, trainers & more': 'Buscar entrenamientos, nutrición, entrenadores...',
      'Billing FAQ': 'Preguntas frecuentes de facturación',
      'All caught up!': '¡Todo al día!',
      'Your Cart is Empty': 'Tu carrito está vacío'
    },
    fr: {
      'Home': 'Accueil',
      'Workouts': 'Entraînements',
      'Nutrition': 'Nutrition',
      'Shop': 'Boutique',
      'Trainers': 'Entraîneurs',
      'Pricing': 'Tarifs',
      'Calculators': 'Calculateurs',
      'Community': 'Communauté',
      'Dashboard': 'Tableau de bord',
      'Login': 'Connexion',
      'Register': 'Inscription',
      'Start Session': 'Démarrer la session',
      'Open Scanner': 'Ouvrir le scanner',
      'Unlock With Pro': 'Débloquer avec Pro',
      'Search workouts, nutrition, trainers & more': 'Rechercher entraînements, nutrition, entraîneurs...',
      'Billing FAQ': 'FAQ de facturation',
      'All caught up!': 'Tout est à jour !',
      'Your Cart is Empty': 'Votre panier est vide'
    },
    de: {
      'Home': 'Startseite',
      'Workouts': 'Workouts',
      'Nutrition': 'Ernährung',
      'Shop': 'Shop',
      'Trainers': 'Trainer',
      'Pricing': 'Preise',
      'Calculators': 'Rechner',
      'Community': 'Community',
      'Dashboard': 'Dashboard',
      'Login': 'Anmelden',
      'Register': 'Registrieren',
      'Start Session': 'Sitzung starten',
      'Open Scanner': 'Scanner öffnen',
      'Unlock With Pro': 'Mit Pro freischalten',
      'Search workouts, nutrition, trainers & more': 'Workouts, Ernährung & Trainer suchen...',
      'Billing FAQ': 'Abrechnungs-FAQ',
      'All caught up!': 'Alles erledigt!',
      'Your Cart is Empty': 'Dein Warenkorb ist leer'
    },
    ar: {
      'Home': 'الرئيسية',
      'Workouts': 'التمارين',
      'Nutrition': 'التغذية',
      'Shop': 'المتجر',
      'Trainers': 'المدربون',
      'Pricing': 'الأسعار',
      'Calculators': 'الحاسبات',
      'Community': 'المجتمع',
      'Dashboard': 'لوحة التحكم',
      'Login': 'تسجيل الدخول',
      'Register': 'إنشاء حساب',
      'Start Session': 'ابدأ الجلسة',
      'Open Scanner': 'افتح الماسح',
      'Unlock With Pro': 'فتح مع النسخة الاحترافية',
      'Search workouts, nutrition, trainers & more': 'ابحث عن التمارين والتغذية والمدربين...',
      'Billing FAQ': 'الأسئلة الشائعة حول الدفع',
      'All caught up!': 'تمت متابعة كل شيء!',
      'Your Cart is Empty': 'سلتك فارغة'
    },
    ur: {
      'Home': 'ہوم',
      'Workouts': 'ورک آؤٹس',
      'Nutrition': 'غذائیت',
      'Shop': 'دکان',
      'Trainers': 'ٹرینرز',
      'Pricing': 'قیمتیں',
      'Calculators': 'کیلکولیٹر',
      'Community': 'کمیونٹی',
      'Dashboard': 'ڈیش بورڈ',
      'Login': 'لاگ ان',
      'Register': 'رجسٹر کریں',
      'Start Session': 'سیشن شروع کریں',
      'Open Scanner': 'اسکینر کھولیں',
      'Unlock With Pro': 'پرو کے ساتھ انلاک کریں',
      'Search workouts, nutrition, trainers & more': 'ورک آؤٹس، غذائیت اور ٹرینرز تلاش کریں...',
      'Billing FAQ': 'بلنگ کے اکثر پوچھے گئے سوالات',
      'All caught up!': 'سب اپ ٹو ڈیٹ ہے!',
      'Your Cart is Empty': 'آپ کی کارٹ خالی ہے'
    },
    hi: {
      'Home': 'होम',
      'Workouts': 'व्यायाम',
      'Nutrition': 'पोषण',
      'Shop': 'दुकान',
      'Trainers': 'प्रशिक्षक',
      'Pricing': 'मूल्य',
      'Calculators': 'कैलकुलेटर',
      'Community': 'समुदाय',
      'Dashboard': 'डैशबोर्ड',
      'Login': 'लॉग इन',
      'Register': 'रजिस्टर करें',
      'Start Session': 'सत्र शुरू करें',
      'Open Scanner': 'स्कैनर खोलें',
      'Unlock With Pro': 'प्रो के साथ अनलॉक करें',
      'Search workouts, nutrition, trainers & more': 'व्यायाम, पोषण और प्रशिक्षक खोजें...',
      'Billing FAQ': 'बिलिंग संबंधी अक्सर पूछे जाने वाले प्रश्न',
      'All caught up!': 'सब कुछ अपडेट है!',
      'Your Cart is Empty': 'आपकी कार्ट खाली है'
    },
    zh: {
      'Home': '首页',
      'Workouts': '锻炼',
      'Nutrition': '营养',
      'Shop': '商店',
      'Trainers': '教练',
      'Pricing': '价格',
      'Calculators': '计算器',
      'Community': '社区',
      'Dashboard': '仪表板',
      'Login': '登录',
      'Register': '注册',
      'Start Session': '开始会话',
      'Open Scanner': '打开扫描仪',
      'Unlock With Pro': '解锁专业版',
      'Search workouts, nutrition, trainers & more': '搜索锻炼、营养和教练...',
      'Billing FAQ': '账单常见问题',
      'All caught up!': '已全部处理完毕！',
      'Your Cart is Empty': '您的购物车是空的'
    },
    ja: {
      'Home': 'ホーム',
      'Workouts': 'ワークアウト',
      'Nutrition': '栄養',
      'Shop': 'ショップ',
      'Trainers': 'トレーナー',
      'Pricing': '料金',
      'Calculators': '計算ツール',
      'Community': 'コミュニティ',
      'Dashboard': 'ダッシュボード',
      'Login': 'ログイン',
      'Register': '新規登録',
      'Start Session': 'セッション開始',
      'Open Scanner': 'スキャナーを開く',
      'Unlock With Pro': 'Proでロック解除',
      'Search workouts, nutrition, trainers & more': 'ワークアウト、栄養、トレーナーを検索...',
      'Billing FAQ': '請求に関するFAQ',
      'All caught up!': 'すべて完了しました！',
      'Your Cart is Empty': 'カートは空です'
    },
    pt: {
      'Home': 'Início',
      'Workouts': 'Treinos',
      'Nutrition': 'Nutrição',
      'Shop': 'Loja',
      'Trainers': 'Treinadores',
      'Pricing': 'Preços',
      'Calculators': 'Calculadoras',
      'Community': 'Comunidade',
      'Dashboard': 'Painel',
      'Login': 'Entrar',
      'Register': 'Registrar',
      'Start Session': 'Iniciar sessão',
      'Open Scanner': 'Abrir scanner',
      'Unlock With Pro': 'Desbloquear com Pro',
      'Search workouts, nutrition, trainers & more': 'Pesquisar treinos, nutrição e mais...',
      'Billing FAQ': 'Perguntas Frequentes de Cobrança',
      'All caught up!': 'Tudo atualizado!',
      'Your Cart is Empty': 'Seu carrinho está vazio'
    },
    tr: {
      'Home': 'Ana Sayfa',
      'Workouts': 'Egzersizler',
      'Nutrition': 'Beslenme',
      'Shop': 'Mağaza',
      'Trainers': 'Eğitmenler',
      'Pricing': 'Fiyatlar',
      'Calculators': 'Hesaplayıcılar',
      'Community': 'Topluluk',
      'Dashboard': 'Panel',
      'Login': 'Giriş',
      'Register': 'Kayıt Ol',
      'Start Session': 'Oturumu Başlat',
      'Open Scanner': 'Tarayıcıyı Aç',
      'Unlock With Pro': 'Pro ile Kilidi Aç',
      'Search workouts, nutrition, trainers & more': 'Egzersizler, beslenme ve daha fazlasını arayın...',
      'Billing FAQ': 'Fatura SSS',
      'All caught up!': 'Hepsi tamamlandı!',
      'Your Cart is Empty': 'Sepetiniz boş'
    },
    ru: {
      'Home': 'Главная',
      'Workouts': 'Тренировки',
      'Nutrition': 'Питание',
      'Shop': 'Магазин',
      'Trainers': 'Тренеры',
      'Pricing': 'Цены',
      'Calculators': 'Калькуляторы',
      'Community': 'Сообщество',
      'Dashboard': 'Панель',
      'Login': 'Войти',
      'Register': 'Регистрация',
      'Start Session': 'Начать сессию',
      'Open Scanner': 'Открыть сканер',
      'Unlock With Pro': 'Разблокировать Pro',
      'Search workouts, nutrition, trainers & more': 'Поиск тренировок, питания и тренеров...',
      'Billing FAQ': 'Часто задаваемые вопросы по оплате',
      'All caught up!': 'Все просмотрено!',
      'Your Cart is Empty': 'Ваша корзина пуста'
    }
  };

  function initI18n() {
    const currentLang = document.documentElement.getAttribute('lang') || 'en';
    localStorage.setItem(STORAGE_KEY, currentLang);

    // Ensure correct direction
    if (RTL_LANGS.includes(currentLang)) {
      document.documentElement.setAttribute('dir', 'rtl');
    } else {
      document.documentElement.setAttribute('dir', 'ltr');
    }

    // Bind all language switcher dropdowns
    document.querySelectorAll('.cca-lang-switcher').forEach(setupDropdown);

    // Perform client text replacement if not English
    if (currentLang !== 'en' && I18N_DICTIONARY[currentLang]) {
      applyClientDictionary(I18N_DICTIONARY[currentLang]);
    }
  }

  function applyClientDictionary(dict) {
    // 1. Navigation links
    document.querySelectorAll('.nav-links a, .side-links a, .auth-tabs button').forEach(el => {
      const txt = el.textContent.trim();
      if (dict[txt]) el.textContent = dict[txt];
    });

    // 2. Search placeholder
    const searchIn = document.getElementById('searchIn');
    if (searchIn && dict['Search workouts, nutrition, trainers & more']) {
      searchIn.setAttribute('placeholder', dict['Search workouts, nutrition, trainers & more']);
    }

    // 3. CTA Buttons & Common tags
    document.querySelectorAll('.btn, .wk-btn, .lock-tag').forEach(el => {
      const txt = el.textContent.trim();
      if (dict[txt]) el.textContent = dict[txt];
    });
  }

  function setupDropdown(switcher) {
    const toggleBtn = switcher.querySelector('.cca-lang-btn');
    const menu = switcher.querySelector('.cca-lang-menu');
    const searchInput = switcher.querySelector('.cca-lang-search');
    const options = switcher.querySelectorAll('.cca-lang-opt');
    const autoTranslateBtn = switcher.querySelector('.cca-lang-auto-btn');

    if (!toggleBtn || !menu) return;

    function openMenu() {
      switcher.classList.add('is-open');
      toggleBtn.setAttribute('aria-expanded', 'true');
      if (searchInput) {
        searchInput.value = '';
        filterOptions('');
        setTimeout(() => searchInput.focus(), 80);
      }
    }

    function closeMenu() {
      switcher.classList.remove('is-open');
      toggleBtn.setAttribute('aria-expanded', 'false');
    }

    toggleBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (switcher.classList.contains('is-open')) {
        closeMenu();
      } else {
        // Close any other open menus first
        document.querySelectorAll('.cca-lang-switcher.is-open').forEach(s => s.classList.remove('is-open'));
        openMenu();
      }
    });

    // Filter languages
    if (searchInput) {
      searchInput.addEventListener('input', function () {
        filterOptions(this.value.trim().toLowerCase());
      });
      searchInput.addEventListener('click', function(e) {
        e.stopPropagation();
      });
    }

    function filterOptions(query) {
      options.forEach(opt => {
        const text = (opt.dataset.name || '' + opt.textContent).toLowerCase();
        if (text.includes(query)) {
          opt.style.display = 'flex';
        } else {
          opt.style.display = 'none';
        }
      });
    }

    // Selection click
    options.forEach(opt => {
      opt.addEventListener('click', function (e) {
        e.preventDefault();
        const code = this.dataset.code;
        if (!code) return;
        selectLanguage(code);
      });
    });

    // Universal auto-translator toggle (for all 130+ world countries)
    if (autoTranslateBtn) {
      autoTranslateBtn.addEventListener('click', function (e) {
        e.preventDefault();
        loadUniversalTranslator();
      });
    }

    // Close on outside click
    document.addEventListener('click', function (e) {
      if (!switcher.contains(e.target)) {
        closeMenu();
      }
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && switcher.classList.contains('is-open')) {
        closeMenu();
        toggleBtn.focus();
      }
    });
  }

  function selectLanguage(code) {
    localStorage.setItem(STORAGE_KEY, code);
    const baseUrl = (window.TF && window.TF.baseUrl) ? window.TF.baseUrl : '';
    window.location.href = baseUrl + '/api/set-language.php?lang=' + encodeURIComponent(code);
  }

  // Universal Google Translate Loader for complete global country coverage
  let gTranslateLoaded = false;
  function loadUniversalTranslator() {
    if (gTranslateLoaded) {
      const el = document.getElementById('google_translate_element');
      if (el) el.scrollIntoView({ behavior: 'smooth' });
      return;
    }

    gTranslateLoaded = true;
    let target = document.getElementById('cca_universal_translate_bar');
    if (!target) {
      target = document.createElement('div');
      target.id = 'cca_universal_translate_bar';
      target.className = 'cca-universal-translate-bar';
      target.innerHTML = `
        <div class="cca-translate-box">
          <span class="cca-translate-title">🌐 Worldwide Country Language Selector</span>
          <div id="google_translate_element"></div>
          <button type="button" class="cca-translate-close" title="Close" aria-label="Close">&times;</button>
        </div>
      `;
      document.body.prepend(target);

      target.querySelector('.cca-translate-close').addEventListener('click', () => {
        target.style.display = 'none';
      });
    }
    target.style.display = 'block';

    window.googleTranslateElementInit = function () {
      new window.google.translate.TranslateElement({
        pageLanguage: 'en',
        layout: window.google.translate.TranslateElement.InlineLayout.SIMPLE,
        autoDisplay: false
      }, 'google_translate_element');
    };

    const s = document.createElement('script');
    s.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
    document.body.appendChild(s);
  }

  // Self-execute on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initI18n);
  } else {
    initI18n();
  }

  // Expose on window
  window.CCA_I18N = {
    changeLanguage: selectLanguage,
    openUniversalTranslate: loadUniversalTranslator
  };
})();
