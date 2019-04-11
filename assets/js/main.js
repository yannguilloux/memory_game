/**
 * Créer un alias de jquery pour sécuriser la classe
 */
(function ($) {

    /**
     * On créer la liste des méthodes du plugin jQuery
     * @type {{init: init, checkCard: checkCard, showCard: showCard, hideCard: hideCard, incTimer: incTimer}}
     */
    var methods = {

        /**
         * Méthode d'initialisation
         */
        init: function () {

            this.data('state', 'stop');

        },

        /**
         * Méthode permettant gérer le choix d'une carte par l'utilisateur et d'executer le résultat
         * @param card
         * @returns {boolean}
         */
        checkCard: function (card) {

            // Stockage de l'object DOM pour un accès futur dans une closure ou un callback
            var self = this;

            if (this.data('state') === 'stop') {
                $('#progress #time_elapsed').animate({width: '100%'}, ((maxtime - timer) * 1000), "linear", function () {
                    self.Memory('checkCard');
                });

                this.data('state', 'run');
            }

            var data = {};

            if (typeof card !== 'undefined') {
                // On stocke l'élément jQuery DOM dans une variable javascript affin d'optimiser l'execution
                // et de permettre son utilsation après l'appel ajax car le contexte sera, alors, différent
                var $card = $(card);

                // si la carte est déjà retourné on bloque la suite
                if ($card.hasClass('front')) {
                    return false;
                }

                // Création d'un container de données de style formulaire
                data = {
                    'card_index': $('ul#card_list li').index(card)
                };
            } else {

                // Si aucune carte n'est spécifié en paramètre on relance une partie
                data = {
                    'new_game': 1
                };
            }

            // Appel de la résolution en ajax
            $.ajax({
                type: 'POST',
                url: 'index.php',
                data: data,
                dataType: 'json',
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR, textStatus, errorThrown);
                },
                success: function (data) {

                    // on resynchronise le timer client avec le timer server
                    if (data.timer) {
                        $('#progress #time_elapsed').width((data.timer / maxtime * 100) + 'px');
                    }

                    if (data.state === 'lost') {

                        // la partie est perdu

                        alert('Partie perdue...');
                        document.location.reload(true);

                    } else if (data.state === 'won') {

                        // la partie est gagné

                        if (typeof $card !== 'undefined') {
                            // On modifie la classe css de la carte
                            $card.removeClass('error');
                            $card.addClass('success');

                            // on montre la carte
                            self.Memory('showCard', $card, data);
                        }

                        // on laisse le temps à la dernière carte de ce retourner
                        setTimeout(function () {
                            alert('Partie gagnée !!!');
                            document.location.reload(true);
                        }, 500);

                    } else if (data.state === 'first-pick') {
                        // C'est la première carte de la paire qui a été choisie
                        // Si la carte existe on la montre
                        if (typeof $card !== 'undefined') {
                            // On supprime les classes css de la carte
                            $card.removeClass('error');
                            $card.removeClass('success');

                            // on montre la carte
                            self.Memory('showCard', $card, data);

                        }

                    } else if (data.state === 'success') {

                        // Si la carte existe on la montre
                        if (typeof $card !== 'undefined') {
                            // On modifie la classe css de la carte
                            $card.removeClass('error');
                            $card.addClass('success');
                            $($('#card_list li')[data.last_card_index]).addClass('success');

                            // on montre la carte
                            self.Memory('showCard', $card, data);
                        }

                    } else {

                        // La deuxième carte ne correspond pas
                        // Si la carte existe on la cache
                        if (typeof $card !== 'undefined') {

                            // On modifie la classe css de la carte
                            $card.removeClass('success');
                            $card.addClass('error');
                            $($('#card_list li')[data.last_card_index]).addClass('error');

                            // On programme le retournement de la paire non-confirme au bout d'une seconde
                            setTimeout(function () {
                                // on cache les cartes de la paire
                                self.Memory('hideCard', $card, $($('#card_list li')[data.last_card_index]));
                            }, 1000);

                            // on montre la carte
                            self.Memory('showCard', $card, data);
                        }

                    }


                }
            });

        },

        /**
         * Méthode permettant de montrer une carte
         * @param $card
         * @param data
         */
        showCard: function ($card, data) {
            $card.removeClass('back');
            $card.addClass('front');
            // on modifie la position de l'image de fond du sprite des fruits
            $card.css('background-position-y', -(parseFloat(data.card.pair_id) - 1) * $card.height());
        },

        /**
         * Méthode permettant de cacher une carte
         * @param $card
         * @param $last_card
         */
        hideCard: function ($card, $last_card) {
            $last_card.removeClass('front');
            $last_card.addClass('back');
            $last_card.removeAttr('style');
            $card.removeClass('front');
            $card.addClass('back');
            $card.removeAttr('style');
        },
    };

    /**
     * Implémentation du plugin Memory
     * @param methodOrOptions Méthode du plugin appelé
     * @returns {*}
     * @constructor
     */
    $.fn.Memory = function (methodOrOptions) {
        if (methods[methodOrOptions]) {
            return methods[methodOrOptions].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof methodOrOptions === 'object' || !methodOrOptions) {
            // Par défaut la méthode appelé est init
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + methodOrOptions + ' does not exist on jQuery.tooltip');
        }
    };

})(jQuery);


$(document).ready(function () {

    /**
     * On intitialise le plugin Memory
     */
    $("#memory_game").Memory();


    /**
     * On écoute le clique sur une des cartes
     */
    $('ul#card_list li').on('click', function () {

        /**
         * On vérifie cette carte afin de déterminer les actions a entreprendre
         * @var this l'objet DOM de la carte cliqué
         */
        $("#memory_game").Memory('checkCard', this);

    });

});