<?php
/* ============ CORE CALORIE ADVISOR — Internationalization (i18n) Engine ============
   Supports 12+ primary localized languages with full RTL capability (Arabic, Urdu)
   plus universal global translation coverage for all countries worldwide. */

if (!function_exists('get_supported_languages')) {
    function get_supported_languages(): array {
        return [
            'en' => ['code' => 'en', 'name' => 'English',    'native' => 'English',     'flag' => '🇬🇧', 'dir' => 'ltr'],
            'es' => ['code' => 'es', 'name' => 'Spanish',    'native' => 'Español',     'flag' => '🇪🇸', 'dir' => 'ltr'],
            'fr' => ['code' => 'fr', 'name' => 'French',     'native' => 'Français',    'flag' => '🇫🇷', 'dir' => 'ltr'],
            'de' => ['code' => 'de', 'name' => 'German',     'native' => 'Deutsch',     'flag' => '🇩🇪', 'dir' => 'ltr'],
            'ar' => ['code' => 'ar', 'name' => 'Arabic',     'native' => 'العربية',      'flag' => '🇸🇦', 'dir' => 'rtl'],
            'ur' => ['code' => 'ur', 'name' => 'Urdu',       'native' => 'اردو',        'flag' => '🇵🇰', 'dir' => 'rtl'],
            'hi' => ['code' => 'hi', 'name' => 'Hindi',      'native' => 'हिन्दी',       'flag' => '🇮🇳', 'dir' => 'ltr'],
            'zh' => ['code' => 'zh', 'name' => 'Chinese',    'native' => '中文',        'flag' => '🇨🇳', 'dir' => 'ltr'],
            'ja' => ['code' => 'ja', 'name' => 'Japanese',   'native' => '日本語',      'flag' => '🇯🇵', 'dir' => 'ltr'],
            'pt' => ['code' => 'pt', 'name' => 'Portuguese', 'native' => 'Português',   'flag' => '🇧🇷', 'dir' => 'ltr'],
            'tr' => ['code' => 'tr', 'name' => 'Turkish',    'native' => 'Türkçe',      'flag' => '🇹🇷', 'dir' => 'ltr'],
            'ru' => ['code' => 'ru', 'name' => 'Russian',    'native' => 'Русский',     'flag' => '🇷🇺', 'dir' => 'ltr'],
        ];
    }
}

if (!function_exists('current_lang')) {
    function current_lang(): string {
        static $resolvedLang = null;
        if ($resolvedLang !== null) return $resolvedLang;

        $langs = get_supported_languages();

        // 1. Explicit request parameter
        if (isset($_GET['lang'])) {
            $req = strtolower(trim((string)$_GET['lang']));
            if (isset($langs[$req])) {
                $_SESSION['cca_lang'] = $req;
                @setcookie('cca_lang', $req, [
                    'expires'  => time() + 31536000,
                    'path'     => '/',
                    'samesite' => 'Lax',
                    'httponly' => false,
                ]);
                return $resolvedLang = $req;
            }
        }

        // 2. Session preference
        if (!empty($_SESSION['cca_lang']) && isset($langs[$_SESSION['cca_lang']])) {
            return $resolvedLang = $_SESSION['cca_lang'];
        }

        // 3. Cookie preference
        if (!empty($_COOKIE['cca_lang']) && isset($langs[$_COOKIE['cca_lang']])) {
            $_SESSION['cca_lang'] = $_COOKIE['cca_lang'];
            return $resolvedLang = $_COOKIE['cca_lang'];
        }

        // 4. Accept-Language header browser fallback
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $header = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($langs as $code => $info) {
                if (str_starts_with($header, $code) || str_contains($header, ',' . $code)) {
                    $_SESSION['cca_lang'] = $code;
                    return $resolvedLang = $code;
                }
            }
        }

        return $resolvedLang = 'en';
    }
}

if (!function_exists('is_rtl')) {
    function is_rtl(): bool {
        $langs = get_supported_languages();
        $lang = current_lang();
        return ($langs[$lang]['dir'] ?? 'ltr') === 'rtl';
    }
}

if (!function_exists('__')) {
    function __(string $key, string $default = ''): string {
        static $dict = null;
        if ($dict === null) {
            $dict = [
                // Navigation
                'nav_home'            => ['es' => 'Inicio', 'fr' => 'Accueil', 'de' => 'Startseite', 'ar' => 'الرئيسية', 'ur' => 'ہوم', 'hi' => 'होम', 'zh' => '首页', 'ja' => 'ホーム', 'pt' => 'Início', 'tr' => 'Ana Sayfa', 'ru' => 'Главная'],
                'nav_workouts'        => ['es' => 'Entrenamientos', 'fr' => 'Entraînements', 'de' => 'Workouts', 'ar' => 'التمارين', 'ur' => 'ورک آؤٹس', 'hi' => 'व्यायाम', 'zh' => '锻炼', 'ja' => 'ワークアウト', 'pt' => 'Treinos', 'tr' => 'Egzersizler', 'ru' => 'Тренировки'],
                'nav_nutrition'       => ['es' => 'Nutrición', 'fr' => 'Nutrition', 'de' => 'Ernährung', 'ar' => 'التغذية', 'ur' => 'غذائیت', 'hi' => 'पोषण', 'zh' => '营养', 'ja' => '栄養', 'pt' => 'Nutrição', 'tr' => 'Beslenme', 'ru' => 'Питание'],
                'nav_shop'            => ['es' => 'Tienda', 'fr' => 'Boutique', 'de' => 'Shop', 'ar' => 'المتجر', 'ur' => 'دکان', 'hi' => 'दुकान', 'zh' => '商店', 'ja' => 'ショップ', 'pt' => 'Loja', 'tr' => 'Mağaza', 'ru' => 'Магазин'],
                'nav_trainers'        => ['es' => 'Entrenadores', 'fr' => 'Entraîneurs', 'de' => 'Trainer', 'ar' => 'المدربون', 'ur' => 'ٹرینرز', 'hi' => 'प्रशिक्षक', 'zh' => '教练', 'ja' => 'トレーナー', 'pt' => 'Treinadores', 'tr' => 'Eğitmenler', 'ru' => 'Тренеры'],
                'nav_pricing'         => ['es' => 'Precios', 'fr' => 'Tarifs', 'de' => 'Preise', 'ar' => 'الأسعار', 'ur' => 'قیمتیں', 'hi' => 'मूल्य निर्धारण', 'zh' => '价格', 'ja' => '料金', 'pt' => 'Preços', 'tr' => 'Fiyatlar', 'ru' => 'Цены'],
                'nav_dashboard'       => ['es' => 'Panel', 'fr' => 'Tableau de bord', 'de' => 'Dashboard', 'ar' => 'لوحة التحكم', 'ur' => 'ڈیش بورڈ', 'hi' => 'डैशबोर्ड', 'zh' => '仪表板', 'ja' => 'ダッシュボード', 'pt' => 'Painel', 'tr' => 'Panel', 'ru' => 'Панель'],
                'nav_calculators'     => ['es' => 'Calculadoras', 'fr' => 'Calculateurs', 'de' => 'Rechner', 'ar' => 'الحاسبات', 'ur' => 'کیلکولیٹر', 'hi' => 'कैलकुलेटर', 'zh' => '计算器', 'ja' => '計算ツール', 'pt' => 'Calculadoras', 'tr' => 'Hesaplayıcılar', 'ru' => 'Калькуляторы'],
                'nav_community'       => ['es' => 'Comunidad', 'fr' => 'Communauté', 'de' => 'Community', 'ar' => 'المجتمع', 'ur' => 'کمیونٹی', 'hi' => 'समुदाय', 'zh' => '社区', 'ja' => 'コミュニティ', 'pt' => 'Comunidade', 'tr' => 'Topluluk', 'ru' => 'Сообщество'],
                'nav_login'           => ['es' => 'Iniciar sesión', 'fr' => 'Connexion', 'de' => 'Anmelden', 'ar' => 'تسجيل الدخول', 'ur' => 'لاگ ان', 'hi' => 'लॉग इन', 'zh' => '登录', 'ja' => 'ログイン', 'pt' => 'Entrar', 'tr' => 'Giriş', 'ru' => 'Войти'],
                'nav_register'        => ['es' => 'Registrarse', 'fr' => 'Inscription', 'de' => 'Registrieren', 'ar' => 'إنشاء حساب', 'ur' => 'رجسٹر', 'hi' => 'रजिस्टर करें', 'zh' => '注册', 'ja' => '新規登録', 'pt' => 'Registrar', 'tr' => 'Kayıt Ol', 'ru' => 'Регистрация'],
                'nav_logout'          => ['es' => 'Cerrar sesión', 'fr' => 'Déconnexion', 'de' => 'Abmelden', 'ar' => 'تسجيل الخروج', 'ur' => 'لاگ آؤٹ', 'hi' => 'लॉग आउट', 'zh' => '登出', 'ja' => 'ログアウト', 'pt' => 'Sair', 'tr' => 'Çıkış Yap', 'ru' => 'Выйти'],
                'nav_search_ph'       => ['es' => 'Buscar entrenamientos, nutrición y más...', 'fr' => 'Rechercher entraînements, nutrition...', 'de' => 'Workouts, Ernährung & Trainer suchen...', 'ar' => 'ابحث عن التمارين والتغذية والمدربين...', 'ur' => 'ورک آؤٹس، غذائیت اور ٹرینرز تلاش کریں...', 'hi' => 'व्यायाम, पोषण और प्रशिक्षक खोजें...', 'zh' => '搜索锻炼、营养和教练...', 'ja' => 'ワークアウト、栄養、トレーナーを検索...', 'pt' => 'Pesquisar treinos, nutrição e mais...', 'tr' => 'Egzersizler, beslenme ve daha fazlasını arayın...', 'ru' => 'Поиск тренировок, питания и тренеров...'],
                
                // Common Actions
                'btn_start_workout'   => ['es' => 'Comenzar entrenamiento', 'fr' => 'Démarrer l\'entraînement', 'de' => 'Workout starten', 'ar' => 'ابدأ التمرين', 'ur' => 'ورک آؤٹ شروع کریں', 'hi' => 'व्यायाम शुरू करें', 'zh' => '开始锻炼', 'ja' => 'ワークアウト開始', 'pt' => 'Iniciar treino', 'tr' => 'Antrenmana Başla', 'ru' => 'Начать тренировку'],
                'btn_open_scanner'    => ['es' => 'Abrir escáner', 'fr' => 'Ouvrir le scanner', 'de' => 'Scanner öffnen', 'ar' => 'افتح الماسح', 'ur' => 'اسکینر کھولیں', 'hi' => 'स्कैनर खोलें', 'zh' => '打开扫描仪', 'ja' => 'スキャナーを開く', 'pt' => 'Abrir scanner', 'tr' => 'Tarayıcıyı Aç', 'ru' => 'Открыть сканер'],
                'btn_unlock_pro'      => ['es' => 'Desbloquear con Pro', 'fr' => 'Débloquer avec Pro', 'de' => 'Mit Pro freischalten', 'ar' => 'فتح مع النسخة الاحترافية', 'ur' => 'پرو کے ساتھ انلاک کریں', 'hi' => 'प्रो के साथ अनलॉक करें', 'zh' => '解锁专业版', 'ja' => 'Proでロック解除', 'pt' => 'Desbloquear com Pro', 'tr' => 'Pro ile Kilidi Aç', 'ru' => 'Разблокировать Pro'],
                'btn_save'            => ['es' => 'Guardar', 'fr' => 'Enregistrer', 'de' => 'Speichern', 'ar' => 'حفظ', 'ur' => 'محفوظ کریں', 'hi' => 'सहेजें', 'zh' => '保存', 'ja' => '保存', 'pt' => 'Salvar', 'tr' => 'Kaydet', 'ru' => 'Сохранить'],
                'btn_cancel'          => ['es' => 'Cancelar', 'fr' => 'Annuler', 'de' => 'Abbrechen', 'ar' => 'إلغاء', 'ur' => 'منسوخ کریں', 'hi' => 'रद्द करें', 'zh' => '取消', 'ja' => 'キャンセル', 'pt' => 'Cancelar', 'tr' => 'İptal', 'ru' => 'Отмена'],

                // Scanners
                'title_body_scanner'  => ['es' => 'Escáner corporal IA', 'fr' => 'Scanner corporel IA', 'de' => 'KI-Körperscanner', 'ar' => 'ماسح الجسم بالذكاء الاصطناعي', 'ur' => 'اے آئی باڈی اسکینر', 'hi' => 'एआई बॉडी स्कैनर', 'zh' => 'AI身体扫描仪', 'ja' => 'AIボディスキャナー', 'pt' => 'Scanner Corporal IA', 'tr' => 'Yapay Zeka Vücut Tarayıcısı', 'ru' => 'ИИ Сканер тела'],
                'title_food_scanner'  => ['es' => 'Escáner de alimentos IA', 'fr' => 'Scanner d\'aliments IA', 'de' => 'KI-Essensscanner', 'ar' => 'ماسح الطعام بالذكاء الاصطناعي', 'ur' => 'اے آئی فوڈ اسکینر', 'hi' => 'एआई फूड स्कैनर', 'zh' => 'AI食物扫描仪', 'ja' => 'AIフードスキャナー', 'pt' => 'Scanner de Alimentos IA', 'tr' => 'Yapay Zeka Yemek Tarayıcısı', 'ru' => 'ИИ Сканер еды'],
                
                // General
                'lang_select'         => ['es' => 'Idioma', 'fr' => 'Langue', 'de' => 'Sprache', 'ar' => 'اللغة', 'ur' => 'زبان', 'hi' => 'भाषा', 'zh' => '语言', 'ja' => '言語', 'pt' => 'Idioma', 'tr' => 'Dil', 'ru' => 'Язык'],
                'lang_change'         => ['es' => 'Cambiar idioma', 'fr' => 'Changer de langue', 'de' => 'Sprache ändern', 'ar' => 'تغيير اللغة', 'ur' => 'زبان تبدیل کریں', 'hi' => 'भाषा बदलें', 'zh' => '切换语言', 'ja' => '言語を変更', 'pt' => 'Mudar idioma', 'tr' => 'Dili Değiştir', 'ru' => 'Сменить язык'],
            ];
        }

        $lang = current_lang();
        if ($lang === 'en') {
            return $default !== '' ? $default : $key;
        }

        if (isset($dict[$key][$lang])) {
            return $dict[$key][$lang];
        }

        return $default !== '' ? $default : $key;
    }
}
