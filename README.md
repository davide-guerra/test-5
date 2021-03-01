# Test 5 per Elabora Next
1. utilizzare una nuova installazione wordpress + woocommerce;
2. creare un attributo prodotto brand con alcuni valori di test;
3. creare un attributo prodotto colore con alcuni valori di test;
4. creare un plugin che ogni 30 minuti crea un nuovo prodotto semplice con valori random per
nome, descrizione, prezzo, attributi brand e colore, quantità;
5. il plugin incrementa o decrementa di 1 la quantità di un prodotto casuale creato in precedenza.

>**N.B.** È possibile eseguire manualmente il cronjob utilizzando il plugin WP Crontrol già installato ed eseguendo l'evento <code>create_random_products</code>.
