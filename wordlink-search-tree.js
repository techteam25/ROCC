(function (wordLinkTerms) {
    // Function representing a node in the word link search tree
    function WordNode() {
        this.isWordLink = false;
        this.childWords = {};
    }

    function WordLinkSearchTree() {
        this.root = new WordNode();
    }

    WordLinkSearchTree.prototype.insertTerm = function (word) {
        const words = this.splitBeforeAndAfterAnyNonLetters(word);
        let currentNode = this.root;

        for (const w of words) {
            const nextNode = currentNode.childWords[w.toLowerCase()] || new WordNode();
            currentNode.childWords[w.toLowerCase()] = nextNode;
            currentNode = nextNode;
        }
        currentNode.isWordLink = true;
    };

    WordLinkSearchTree.prototype.splitOnWordLinks = function (text) {
        const words = this.splitBeforeAndAfterAnyNonLetters(text);
        let resultPhrases = [];
        let nonWordLinkPhrase = "";

        while (words.length > 0) {
            const wordLinkPhrase = this.getIfWordLink(words, this.root);

            if (wordLinkPhrase === "") {
                nonWordLinkPhrase += words.shift();
            } else {
                resultPhrases.push(nonWordLinkPhrase);
                resultPhrases.push(wordLinkPhrase);
                nonWordLinkPhrase = "";
            }
        }

        resultPhrases.push(nonWordLinkPhrase);
        resultPhrases = resultPhrases.filter(item => item !== "");

        return resultPhrases;
    };

    WordLinkSearchTree.prototype.getIfWordLink = function (words, currentNode) {
        if (words.length > 0) {
            const word = words.shift();

            if (currentNode.childWords[word.toLowerCase()]) {
                const nextNode = currentNode.childWords[word.toLowerCase()];
                const wordLink = this.getIfWordLink(words, nextNode);

                if (nextNode.isWordLink || wordLink !== "") {
                    return word + wordLink;
                }
            }
            words.unshift(word);
        }
        return "";
    };

    WordLinkSearchTree.prototype.splitBeforeAndAfterAnyNonLetters = function (text) {
        const words = text.split(/(?![a-zA-Z])|(?<![a-zA-Z])/).filter(item => item !== "");
        return words;
    };

    // create an instance of WordLinkSearchTree and add to window object
    window.WSL  = new WordLinkSearchTree();

    for (let term in wordLinkTerms) {
        window.WSL.insertTerm(term)
    }

})(wordLinkTerms);


