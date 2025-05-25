const htmlQuestions = [
    {
        question: "What does HTML stand for?",
        options: [
            "Hyper Text Markup Language",
            "Hot Type Modern Layout",
            "Hybrid Text Marking Language",
            "Home Tool Markup Language"
        ],
        correct: 0
    },
    {
        question: "Which tag is used for creating a hyperlink?",
        options: [
            "&lt;link&gt;",
            "&lt;a&gt;",
            "&lt;href&gt;",
            "&lt;url&gt;"
        ],
        correct: 1
    },
    {
        question: "Which HTML tag is used to define an internal style sheet?",
        options: [
            "&lt;css&gt;",
            "&lt;script&gt;",
            "&lt;style&gt;",
            "&lt;design&gt;"
        ],
        correct: 2
    },
    {
        question: "What is the correct HTML element for inserting a line break?",
        options: [
            "&lt;break&gt;",
            "&lt;lb&gt;",
            "&lt;br&gt;",
            "&lt;newline&gt;"
        ],
        correct: 2
    },
    {
        question: "Which HTML attribute specifies an alternate text for an image?",
        options: [
            "title",
            "alt",
            "description",
            "caption"
        ],
        correct: 1
    }
];

const cssQuestions = [
    {
        question: "What does CSS stand for?",
        options: [
            "Creative Style Sheets",
            "Cascading Style Sheets",
            "Computer Style Sheets",
            "Colorful Style Sheets"
        ],
        correct: 1
    },
    {
        question: "Which CSS property is used to change the text color?",
        options: [
            "text-color",
            "font-color",
            "color",
            "text-style"
        ],
        correct: 2
    },
    {
        question: "How do you select an element with class 'header' in CSS?",
        options: [
            "#header",
            ".header",
            "header",
            "*header"
        ],
        correct: 1
    },
    {
        question: "What is the default value of the position property?",
        options: [
            "relative",
            "fixed",
            "absolute",
            "static"
        ],
        correct: 3
    },
    {
        question: "Which CSS property controls the spacing between elements?",
        options: [
            "spacing",
            "margin",
            "padding",
            "gap"
        ],
        correct: 1
    }
];

const javascriptQuestions = [
    {
        question: "What is the correct way to declare a JavaScript variable?",
        options: [
            "variable myVar;",
            "var myVar;",
            "v myVar;",
            "set myVar;"
        ],
        correct: 1
    },
    {
        question: "Which operator is used for strict equality comparison in JavaScript?",
        options: [
            "==",
            "=",
            "===",
            "equals"
        ],
        correct: 2
    },
    {
        question: "What is the correct way to write a JavaScript array?",
        options: [
            "var colors = (1:'red', 2:'green', 3:'blue')",
            "var colors = 'red', 'green', 'blue'",
            "var colors = ['red', 'green', 'blue']",
            "var colors = 'red'..'blue'"
        ],
        correct: 2
    },
    {
        question: "How do you write a function in JavaScript?",
        options: [
            "function = myFunction()",
            "function:myFunction()",
            "function myFunction()",
            "def myFunction()"
        ],
        correct: 2
    },
    {
        question: "Which method is used to add elements to the end of an array?",
        options: [
            "append()",
            "push()",
            "add()",
            "insert()"
        ],
        correct: 1
    }
];



const intermediateJavaScriptQuestions = [
    {
        question: "What is the correct way to write a JavaScript for loop?",
        code: `for (let i = 0; i < 5; i++) {
    // Your code here
}`,
        correct: `for (let i = 0; i < 5; i++) { console.log(i); }`
    },
    {
        question: "How do you create a JavaScript object with a name and age property?",
        code: `let person = {
    // Your code here
};`,
        correct: `let person = { name: "John", age: 30 };`
    },
    {
        question: "How do you define a JavaScript function that takes two parameters and returns their sum?",
        code: `function sum(a, b) {
    // Your code here
}`,
        correct: `function sum(a, b) { return a + b; }`
    },
    {
        question: "What is the correct syntax to change the content of an HTML element with id 'myElement' in JavaScript?",
        code: `document. // Your code here`,
        correct: `document.getElementById('myElement').innerHTML = 'New content';`
    },
    {
        question: "How do you check if a variable 'x' is an array in JavaScript?",
        code: `Array. // Your code here`,
        correct: `Array.isArray(x);`
    }
];





const advancedJavaScriptQuestions = [
    {
        question: "How do you create a JavaScript Promise that resolves after 1 second?",
        code: `let myPromise = new Promise((resolve, reject) => {
    // Your code here
});`,
        correct: `let myPromise = new Promise((resolve, reject) => {
    setTimeout(() => resolve('Done!'), 1000);
});`
    },
    {
        question: "How do you define an asynchronous function in JavaScript?",
        code: `async function myAsyncFunction() {
    // Your code here
}`,
        correct: `async function myAsyncFunction() {
    let result = await someAsyncTask();
    return result;
}`
    },
    {
        question: "How do you destructure an object to get 'name' and 'age' properties in JavaScript?",
        code: `let { // Your code here } = person;`,
        correct: `let { name, age } = person;`
    },
    {
        question: "How do you use the spread operator to copy an object and add a new property?",
        code: `let newPerson = { // Your code here };`,
        correct: `let newPerson = { ...person, city: 'New York' };`
    },
    {
        question: "How do you create a class with a constructor in JavaScript?",
        code: `class Person {
    // Your code here
}`,
        correct: `class Person {
    constructor(name, age) {
        this.name = name;
        this.age = age;
    }
}`
    }
];
