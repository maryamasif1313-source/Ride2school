#include <iostream>
#include <string>
using namespace std;
// ================= DRIVER NODE (LINKED LIST) =================
struct Driver{
    int id;
    string name;
    string mobile;
    string vehicleType;
    string school;
    string area;
    int seats;
    float fare;
    Driver* next;
};
Driver* head = NULL;
// ================= QUEUE =================
struct QueueNode{
    string parentName;
    QueueNode* next;
};
QueueNode* frontQ = NULL;
QueueNode* rearQ = NULL;
// ================= STACK =================
struct StackNode{
    string action;
    StackNode* next;
};
StackNode* topStack = NULL;
// ================= LINKED LIST =================
void addDriver(){
    Driver* d = new Driver();
    cout<<"Driver ID: ";
    cin>>d->id;
    cin.ignore();
    cout<<"Driver Name: ";
    getline(cin,d->name);
    cout<<"Mobile: ";
    getline(cin,d->mobile);
    cout<<"Vehicle Type (van/rickshaw): ";
    getline(cin,d->vehicleType);
    cout<<"School Covered: ";
    getline(cin,d->school);
    cout<<"Area Covered: ";
    getline(cin,d->area);
    cout<<"Available Seats: ";
    cin>>d->seats;
    cout<<"Monthly Fare: ";
    cin>>d->fare;
    d->next=NULL;
    if(head==NULL){
        head=d;
    }
    else{
        Driver* temp=head;
        while(temp->next!=NULL){
            temp=temp->next;
        }
        temp->next=d;
    }
    cout<<"\nDriver Added Successfully\n";
}
void showDrivers(){
    Driver* temp=head;
    while(temp!=NULL){
        cout<<"\n-------------------";
        cout<<"\nDriver Name: "<<temp->name;
        cout<<"\nVehicle: "<<temp->vehicleType;
        cout<<"\nSchool: "<<temp->school;
        cout<<"\nArea: "<<temp->area;
        cout<<"\nSeats: "<<temp->seats;
        cout<<"\nFare: "<<temp->fare;
        cout<<"\nMobile: "<<temp->mobile;
        temp=temp->next;
    }
}
// ================= QUEUE =================
void enqueue(){
    QueueNode* node=new QueueNode();
    cin.ignore();
    cout<<"Parent Name: ";
    getline(cin,node->parentName);
    node->next=NULL;
    if(frontQ==NULL){
        frontQ=rearQ=node;
    }
    else{
        rearQ->next=node;
        rearQ=node;
    }
    cout<<"Added To Registration Queue\n";
}
void dequeue(){
    if(frontQ==NULL){
        cout<<"Queue Empty\n";
        return;
    }
    QueueNode* temp=frontQ;
    cout<<"Processing Parent: "
        <<temp->parentName<<endl;
    frontQ=frontQ->next;
    delete temp;
}
// ================= STACK =================
void push(string action){
    StackNode* node=new StackNode();
    node->action=action;
    node->next=topStack;
    topStack=node;
}
void pop(){
    if(topStack==NULL){
        cout<<"Nothing To Undo\n";
        return;
    }
    cout<<"Undo: "
        <<topStack->action<<endl;
    StackNode* temp=topStack;
    topStack=topStack->next;
    delete temp;
}
// ================= SEARCH =================
void searchDriver(){
    string school,area;
    cin.ignore();
    cout<<"School Required: ";
    getline(cin,school);
    cout<<"Area Required: ";
    getline(cin,area);
    Driver* temp=head;
    int found=0;
    while(temp!=NULL){
        if(temp->school==school &&
           temp->area==area){
            found++;
            cout<<"\nFound Driver\n";
            cout<<"Name: "
                <<temp->name<<endl;
            cout<<"Vehicle: "
                <<temp->vehicleType<<endl;
            cout<<"Seats: "
                <<temp->seats<<endl;
            cout<<"Fare: "
                <<temp->fare<<endl;
        }
        temp=temp->next;
    }
    if(found==0){
        cout<<"No Driver Found\n";
    }
}
// ================= BUBBLE SORT =================
void sortFare(){
    if(head==NULL)
        return;
    for(Driver* i=head;i!=NULL;i=i->next){
        for(Driver* j=head;
            j->next!=NULL;
            j=j->next){
            if(j->fare >
               j->next->fare){
                swap(j->fare,j->next->fare);
                swap(j->name,j->next->name);
                swap(j->school,j->next->school);
                swap(j->area,j->next->area);
                swap(j->vehicleType,j->next->vehicleType);
                swap(j->mobile,j->next->mobile);
                swap(j->seats,j->next->seats);
            }
        }
    }
    cout<<"Sorted By Lowest Fare\n";
}
// ================= BOOK DRIVER =================
void bookDriver(){
    string name;
    cin.ignore();
    cout<<"Enter Driver Name: ";
    getline(cin,name);
    Driver* temp=head;
    while(temp!=NULL){
        if(temp->name==name){
            if(temp->seats>0){
                temp->seats--;
                cout<<"Booking Confirmed\n";
                push("Booking Done");
                return;
            }
            else{
                cout<<"No Seats Left\n";
                return;
            }
        }
        temp=temp->next;
    }
    cout<<"Driver Not Found\n";
}
// ================= MAIN =================
int main(){
    int choice;
    do{
        cout<<"\n1 Add Driver";
        cout<<"\n2 Show Drivers";
        cout<<"\n3 Registration Queue";
        cout<<"\n4 Process Queue";
        cout<<"\n5 Search Driver";
        cout<<"\n6 Sort By Fare";
        cout<<"\n7 Book Driver";
        cout<<"\n8 Undo";
        cout<<"\n0 Exit";
        cout<<"\nChoice: ";
        cin>>choice;
        switch(choice){
        case 1:
            addDriver();
            push("Driver Added");
            break;
        case 2:
            showDrivers();
            break;
        case 3:
            enqueue();
            break;
        case 4:
            dequeue();
            break;
        case 5:
            searchDriver();
            break;
        case 6:
            sortFare();
            break;
        case 7:
            bookDriver();
            break;
        case 8:
            pop();
            break;
        }
    }while(choice!=0);
    return 0;
}